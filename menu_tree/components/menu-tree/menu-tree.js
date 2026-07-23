(function ($, Drupal, once) {

  "use strict";

  /**
   * Initialize the expand/collapse toggle button.
   *
   * @param {Element} rootElem - The menu tree component root element.
   * @returs {void}
   */
  const initExpandCollapseToggle = (rootElem) => {
    const buttons = rootElem.querySelectorAll('.toggle-collapse');
    buttons.forEach(button => {
      button.addEventListener('click', e => {
        e.preventDefault();
        let btn = e.target;
        if (e.target.tagName === 'SPAN') {
          btn = e.target.parentElement;
        }
        const state = !btn.classList.contains('collapse');
        const menuId = btn.getAttribute('data-menu-id');
        let selector = `.menu-tree__menu[data-menu-id="${menuId}"] ul:not(.menu-tree__tree) details`;
        if (state) {
          // When expanding, also include the absolute root element (ul.menu-tree).
          selector = `.menu-tree__menu[data-menu-id="${menuId}"] ul details`;
        }
        const elements = rootElem.querySelectorAll(selector);
        elements.forEach(el => {
          el.open = state;
        });
        if (!state) {
          btn.classList.replace('collapse' ,'expand');
          btn.firstElementChild.innerHTML = Drupal.t('Expand all');
        }
        else {
          btn.classList.replace('expand' ,'collapse');
          btn.firstElementChild.innerHTML = Drupal.t('Collapse all');
        }
      });
    });
  }

  /**
   * Initialize menu tree item selection. Add click event listener to BUTTON
   * and LI elements.
   *
   * @param {Element} rootElem - The menu tree component root element.
   * @returns {void}
   */
  const initTreeItemSelection = (rootElem) => {
    const triggers = Array.from(rootElem.querySelectorAll('.menu-tree__tree button.select-item, .menu-tree_tree li'));
    window.addEventListener('click', e => {
      let elem = e.target;

      // Workaround for strange error when click on an SPAN element inside a
      // BUTTON element submits the form.
      if (elem.parentElement.tagName === 'BUTTON') {
        elem = elem.parentElement;
      }

      if (triggers.includes(elem)) {
        e.preventDefault();
        if (elem.tagName === 'BUTTON') {
          e.preventDefault();
        }
        else {
          elem = elem.querySelector('button:not([disabled])');
        }
        triggers.forEach(el => {
          el.classList.remove('selected');
          el.parentElement.classList.remove('selected');
        });
        elem.classList.add('selected');
        elem.parentElement.classList.add('selected');
        setState(elem.getAttribute('data-value'), rootElem);
        addMenuLinkAsChild(elem, rootElem);
        removeEmptyLists(rootElem);
      }
    }, false);
  }

  /**
   * Initialize drag and drop functionality. Add 'dragstart' and 'dragend' event
   * listeners to existing menu items and 'dragover' event listeners to drop
   * targets.
   *
   * @param {Element} rootElem - The menu tree component root element.
   * @returns {void}
   */
  const initDragAndDropHandling = (rootElem) => {
    const menuItem = rootElem.querySelector('.menu-tree__tree li[draggable="true"]');
    if (menuItem) {
      createDraggableMenuLink(rootElem, '', menuItem);
    }
    else {
      setInitialState(rootElem);
    }

    const dropTargets = rootElem.querySelectorAll('ul.menu-tree__tree ul');
    dropTargets.forEach(container => {
      container.addEventListener('dragover', function(e) {
        const draggedElement = rootElem.querySelector('.menu-tree__tree li.dragging');
        const afterElement = getDragAfterElement(container, e.clientY);
        if (afterElement === undefined) {
          this.appendChild(draggedElement);
        }
        else {
          afterElement.parentElement.insertBefore(draggedElement, afterElement);
        }
      })
    });
  }

  /**
   * Initializes the handling of the menu title functionality. Set up event
   * listeners related to managing the behavior of a menu link's title.
   *
   * @param {Element} rootElem - The menu tree component root element.
   * @returns {void}
   */
  const initMenuTitleHandling = (rootElem) => {
    // Add an event listener to the custom jQuery event 'formUpdated' to exactly mirror the value
    // of the menu link title input to the draggable menu link. This is the only place where jQuery
    // is used in this script, but it seems to be the only solution.
    const input = document.querySelector('input[name="menu[title]"]');
    $(input).on('formUpdated', (e) => {
      input.dispatchEvent(new Event('input'));
    });

    // Add an event listener on the "Provide a menu link" checkbox. We add the event listener to
    // the window object to make sure it runs after 'menu_ui' has done its menu link title value
    // mirroring from the node title field.
    window.addEventListener('change', (e) => {
      if (e.target.matches('input[name="menu[enabled]"]')) {
        input.dispatchEvent(new Event('input'));
      }
    });

    // Add an event listener on the menu title input.
    input.addEventListener('input', e => {
      const elem = rootElem.querySelector('.menu-tree__tree li[draggable="true"]');
      if (elem) {
        elem.querySelector('button').innerHTML = e.target.value;
      }
      else {
        createDraggableMenuLink(rootElem, e.target.value);
      }
    });
  }

  /**
   * Initialize the menu tree.
   *
   * @param {Element} rootElem - The menu tree component root element.
   * @returns {void}
   */
  const initMenuTree = (rootElem) => {
    initExpandCollapseToggle(rootElem);
    initTreeItemSelection(rootElem);
    initDragAndDropHandling(rootElem);
    initMenuTitleHandling(rootElem);
  }

  /**
   * Add the new menu item as a child of the selected menu item.
   *
   * @param {HTMLElement} selected - The selected menu parent, a BUTTON element.
   * @param {Element} rootElem - The menu tree component root element.
   * @returns {void}
   */
  const addMenuLinkAsChild = (selected, rootElem) => {
    const elem = rootElem.querySelector('.menu-tree__tree li[draggable="true"]');
    if (!elem) return;
    if (selected.parentElement.tagName === 'SUMMARY') {
      const ul = selected.parentElement.nextElementSibling;
      ul.appendChild(elem);
    }
    else {
      const details = document.createElement('details');
      selected.parentElement.appendChild(details);
      const summary = document.createElement('summary');
      summary.appendChild(selected);
      const ul = document.createElement('ul');
      ul.classList.add('branch');
      ul.appendChild(elem);
      details.appendChild(summary);
      details.appendChild(ul);
    }

    setState(selected.getAttribute('data-value'), rootElem);
  }

  /**
   * Initializes the menu state by selecting the appropriate elements based on
   * the value of the 'menu[menu_parent]' input element.
   *
   * @param {Element} rootElem - The menu tree component root element.
   * @returns {void}
   */
  const setInitialState = (rootElem) => {
    const menuParent = document.querySelector('input[name="menu[menu_parent]"]');
    setState(menuParent.value, rootElem);
  }

  /**
   * Set the state of the menu based on the currently selected parent element.
   *
   * @param {string} value - The ID of the selected parent link.
   * @param {Element} rootElem - The menu tree component root element.
   * @returns {void}
   */
  const setState = (value, rootElem) => {
    // Remove the attribute 'aria-selected' on all elements.
    const ariaSelected = rootElem.querySelectorAll('.menu-tree__tree [aria-selected="true"]');
    ariaSelected.forEach(el => {
      el.setAttribute('aria-selected', 'false');
    })

    // Remove the class 'selected' on all elements.
    const previous = rootElem.querySelectorAll('.menu-tree__tree .selected, .menu-tree__tree .descendant-selected');
    previous.forEach(el => {
      el.classList.remove('selected', 'descendant-selected');
    });

    // Process the selected element.
    let elem = rootElem.querySelector('.menu-tree button[data-value="' + value + '"]');
    if (elem) {
      elem.classList.add('selected'); // BUTTON element.
      elem.parentElement.classList.add('selected'); // SUMMARY element.
      elem.firstElementChild.setAttribute('aria-selected', 'true'); // SPAN element.

      // Set the menu parent input value.
      const menuParent = document.querySelector('input[name="menu[menu_parent]"]');
      menuParent.value = value;

      // Open all parent DETAILS elements.
      while (elem) {
        if (elem.matches('.menu-tree details')) {
          elem.setAttribute('open', 'open');
          if (!elem.firstElementChild.classList.contains('selected')) {
            elem.firstElementChild.classList.add('descendant-selected'); // SUMMARY element.
          }
        }
        elem = elem.parentElement;
      }

      // Save references to the sibling elements in the form.
      let menuItem = rootElem.querySelector('.menu-tree__tree li[draggable="true"]');
      while (menuItem) {
        if (menuItem.tagName === 'LI') {
          // Get the previous sibling element.
          const prevInput = document.querySelector('input[name="menu[prev_sibling]"]');
          if (
            menuItem.previousElementSibling
            && menuItem.previousElementSibling.matches('li')
          ) {
            prevInput.value = menuItem.previousElementSibling.querySelector('button').getAttribute('data-value');
          }
          else {
            prevInput.value = '';
          }

          // Get the next sibling element.
          const nextInput = document.querySelector('input[name="menu[next_sibling]"]');
          if (
            menuItem.nextElementSibling
            && menuItem.nextElementSibling.matches('li')
          ) {
            nextInput.value = menuItem.nextElementSibling.querySelector('button').getAttribute('data-value');
          }
          else {
            nextInput.value = '';
          }

          break;
        }
        menuItem = menuItem.parentElement;
      }
    }
  }

  /**
   * Creates a draggable menu link item and appends it to the selected menu tree.
   *
   * @param {Element} rootElem - The menu tree component root element.
   * @param {string} [menuTitle] - The title displayed for the newly created menu button.
   * @param {HTMLElement} [elem] - The draggable menu link, a LI element.
   * @returns {void}
   */
  const createDraggableMenuLink = (rootElem, menuTitle, elem) => {
    // Create the element if it does not exist.
    if (typeof elem === 'undefined') {
      const selected = rootElem.querySelector('.menu-tree__tree button.selected');
      elem = document.createElement('li');
      elem.setAttribute('draggable', 'true');
      elem.innerHTML = '<button disabled data-value="" data-weight=""><span role="treeitem">' + menuTitle + '</span></button>';
      // @todo We need to make sure we are adding the menu link to the
      //   configured default menu parent link.
      rootElem.querySelector('.menu-tree__tree ul.root').appendChild(elem);
      addMenuLinkAsChild(selected, rootElem);
    }

    // Set the current state.
    setState(elem.parentElement.previousElementSibling.querySelector('button').getAttribute('data-value'), rootElem);

    // Add event listeners.
    elem.addEventListener('dragstart', function() {
      this.classList.add('dragging');
    });
    elem.addEventListener('dragend', function() {
      this.classList.remove('dragging');
      // Update state efter menu link has been placed.
      setState(this.parentElement.previousElementSibling.querySelector('button').getAttribute('data-value'), rootElem);
      removeEmptyLists(rootElem);
    });
  }

  /**
   * Remove empty UL elements and parent DETAILS element to prevent an item
   * rendering as having children.
   *
   * @param {Element} rootElem - The menu tree component root element.
   * @returns {void}
   */
  const removeEmptyLists = (rootElem) => {
    const ulElems = rootElem.querySelectorAll('.menu-tree__tree ul.branch');
    const emptyElems = Array.from(ulElems).filter(elem => elem.childElementCount === 0);
    emptyElems.forEach(elem => {
      const newParent = elem.parentElement.parentElement; // UL element.
      const currParent = elem.parentElement; // DETAILS element.
      const button = currParent.querySelector('button');
      newParent.appendChild(button);
      currParent.remove();
    });
  }

  /**
   * Determines the element after which a dragged item should be placed based on a given vertical position.
   *
   * @param {HTMLElement} container - The parent container element that holds the list items.
   * @param {number} y - The vertical position (usually the mouse or pointer position) used to determine placement.
   * @returns {HTMLElement|null} The element after which the dragged item should be inserted, or null if none is found.
   */
  const getDragAfterElement = (container, y) => {
    const notDraggedItems = [...container.querySelectorAll('.menu-tree__tree ul li:not(.dragging)')]

    return notDraggedItems.reduce((closest, child) => {
      const box = child.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;
      if (offset < 0 && offset > closest.offset) {
        return { offset, element: child }
      } else return closest;
    }, { offset: Number.NEGATIVE_INFINITY }).element;
  }

  /**
   * Attach event listeners and set the initial state of the menu.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.menuTree = {
    attach: function (context) {
      once('menu-tree', '.menu-tree', context).forEach(elem => {
        initMenuTree(elem);
      });
    }
  };

})(jQuery, Drupal, once);
