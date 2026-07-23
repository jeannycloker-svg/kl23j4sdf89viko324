<?php

namespace Drupal\Tests\purge_processor_lateruntime\Kernel;

use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Tests\purge\Kernel\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

/**
 * Tests the LateRuntimeProcessor event subscriber.
 */
#[Group('purge')]
class LateRuntimeProcessorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'purge_purger_test',
    'purge_queuer_test',
    'purge_processor_lateruntime',
  ];

  /**
   * The event subscriber under test.
   *
   * @var \Drupal\purge_processor_lateruntime\EventSubscriber\LateRuntimeProcessor
   */
  protected $subscriber;

  /**
   * {@inheritdoc}
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->initializePurgersService(['good']);
    $this->initializeProcessorsService(['lateruntime']);
    $this->initializeQueuersService(['a']);
    $this->initializeQueueService();
    $this->subscriber = $this->container->get('purge_processor_lateruntime.processor');
  }

  /**
   * Build a TerminateEvent suitable for triggering onKernelTerminate.
   *
   * @return \Symfony\Component\HttpKernel\Event\TerminateEvent
   *   The event object.
   */
  protected function createTerminateEvent(): TerminateEvent {
    return new TerminateEvent(
      $this->container->get('http_kernel'),
      Request::create('/'),
      new Response()
    );
  }

  /**
   * Add invalidation items to the queue and return them.
   *
   * @param int $amount
   *   The number of invalidation items to add.
   *
   * @return \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[]
   *   The added invalidation objects.
   */
  protected function addItemsToQueue(int $amount): array {
    $queuer = $this->purgeQueuers->get('a');
    $invalidations = [];
    for ($i = 0; $i < $amount; $i++) {
      $invalidations[] = $this->purgeInvalidationFactory->get('everything');
    }
    $this->purgeQueue->add($queuer, $invalidations);
    return $invalidations;
  }

  /**
   * Tests that items are claimed and processed on kernel terminate.
   */
  public function testProcessesQueueOnTerminate(): void {
    $this->initializeInvalidationFactoryService();
    $this->addItemsToQueue(3);
    $this->assertEquals(3, $this->purgeQueue->numberOfItems());

    $this->subscriber->onKernelTerminate($this->createTerminateEvent());

    // The good purger marks everything SUCCEEDED, so handleResults() deletes
    // those items from the queue.
    $this->assertEquals(0, $this->purgeQueue->numberOfItems());
  }

  /**
   * Tests that an empty queue is handled gracefully without errors.
   */
  public function testHandlesEmptyQueue(): void {
    $this->assertEquals(0, $this->purgeQueue->numberOfItems());
    // Should not throw or error with an empty queue.
    $this->subscriber->onKernelTerminate($this->createTerminateEvent());
    $this->assertEquals(0, $this->purgeQueue->numberOfItems());
  }

  /**
   * Tests that processing is skipped when the processor is disabled.
   */
  public function testSkipsWhenProcessorDisabled(): void {
    $this->initializeInvalidationFactoryService();
    $this->addItemsToQueue(2);
    $this->assertEquals(2, $this->purgeQueue->numberOfItems());

    // Disable the lateruntime processor and get a fresh subscriber instance
    // so that its internal lazy-load cache is cold.
    $this->initializeProcessorsService([], TRUE);
    $this->container->set('purge_processor_lateruntime.processor', NULL);
    $subscriber = $this->container->get('purge_processor_lateruntime.processor');

    $subscriber->onKernelTerminate($this->createTerminateEvent());

    // Queue should be untouched since the processor is disabled.
    $this->assertEquals(2, $this->purgeQueue->numberOfItems());
  }

  /**
   * Tests that processing is skipped during a config sync (isSyncing = TRUE).
   */
  public function testSkipsDuringConfigSync(): void {
    $this->initializeInvalidationFactoryService();
    $this->addItemsToQueue(2);
    $this->assertEquals(2, $this->purgeQueue->numberOfItems());

    // Replace config.installer with a mock that reports a sync is in progress.
    $installer = $this->createMock(ConfigInstallerInterface::class);
    $installer->method('isSyncing')->willReturn(TRUE);
    $this->container->set('config.installer', $installer);

    $this->subscriber->onKernelTerminate($this->createTerminateEvent());

    // Queue must remain untouched — purgers should never run during
    // config sync.
    $this->assertEquals(2, $this->purgeQueue->numberOfItems());
  }

  /**
   * Tests that processing resumes normally after a config sync completes.
   */
  public function testResumesAfterConfigSync(): void {
    $this->initializeInvalidationFactoryService();
    $this->addItemsToQueue(2);

    // Simulate config sync in progress — queue should not be touched.
    $syncing = $this->createMock(ConfigInstallerInterface::class);
    $syncing->method('isSyncing')->willReturn(TRUE);
    $this->container->set('config.installer', $syncing);
    $this->subscriber->onKernelTerminate($this->createTerminateEvent());
    $this->assertEquals(2, $this->purgeQueue->numberOfItems());

    // Restore a non-syncing installer and get a fresh subscriber so its
    // internal processor cache (set to NULL by the skipped initialize() call)
    // can re-run initialize() and pick up the real services.
    $notSyncing = $this->createMock(ConfigInstallerInterface::class);
    $notSyncing->method('isSyncing')->willReturn(FALSE);
    $this->container->set('config.installer', $notSyncing);
    $this->container->set('purge_processor_lateruntime.processor', NULL);
    $subscriber = $this->container->get('purge_processor_lateruntime.processor');

    $subscriber->onKernelTerminate($this->createTerminateEvent());
    $this->assertEquals(0, $this->purgeQueue->numberOfItems());
  }

  /**
   * Tests that getSubscribedEvents registers the TERMINATE event correctly.
   */
  public function testSubscribedEvents(): void {
    $events = $this->subscriber::getSubscribedEvents();
    $this->assertArrayHasKey(KernelEvents::TERMINATE, $events);
    $terminate = $events[KernelEvents::TERMINATE];
    // Should have at least one listener registered.
    $this->assertNotEmpty($terminate);
    // The listener must reference our handler method.
    $methods = array_column($terminate, 0);
    $this->assertContains('onKernelTerminate', $methods);
    // Priority must be high so it runs before kernel destruction.
    $priorities = array_column($terminate, 1);
    $this->assertGreaterThan(0, max($priorities));
  }

}
