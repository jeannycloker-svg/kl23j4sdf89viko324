<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * The basic "Rating" meta tag.
 *
 * @MetatagTag(
 *   id = "rating",
 *   label = @Translation("Rating"),
 *   description = @Translation("Used to rate content for audience appropriateness. This tag has little known influence on search engine rankings, but can be used by browsers, browser extensions, and apps. The most common options are general, mature, restricted, 14 years, safe for kids. If you follow the <a href='https://en.wikipedia.org/wiki/Content_rating'>RTA Documentation</a> you should enter RTA-5042-1996-1400-1577-RTA"),
 *   name = "rating",
 *   group = "advanced",
 *   weight = 5,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Rating extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
