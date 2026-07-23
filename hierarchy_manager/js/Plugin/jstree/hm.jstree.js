/**
 * @file
 * Hierarchy Manager jsTree JavaScript file.
 */

// Codes run both on normal page loads and when data is loaded by AJAX (or BigPipe!)
// @See https://www.drupal.org/docs/8/api/javascript-api/javascript-api-overview
(function($, Drupal, once) {
  Drupal.behaviors.hmJSTree = {
    attach: function(context, settings) {
      const hmJstree = once('hmJSTree', '.hm-jstree', context);
      // Render all trees.
      hmJstree.forEach(function(hmJstree) {
          const treeContainer = $(hmJstree);
          const parentID = treeContainer.attr('parent-id');
          const searchTextID = (parentID) ? '#hm-jstree-search-' + parentID : '#hm-jstree-search';
          const optionsJson = treeContainer.attr("options");
          const dataURL = treeContainer.attr('data-source') + '&parent=0';
          const updateURL = treeContainer.attr('url-update');
          const confirm = treeContainer.attr("confirm");
          let reload = true;
          let rollback = false;
          let themes = {
              dots: false,
              name: 'default'
          };
          let plugins = ['search', 'sort', 'dnd'];
          // Disable drag & drop where needed.
          if (treeContainer.attr('dnd') === 'disabled') {
            plugins.pop();
          }
          let options;

          if (optionsJson) {
            options = JSON.parse(optionsJson);
            if (options.theme) {
              themes = options.theme;
            }
          }
          // Ajax callback to refresh the tree.
          if (reload) {
            // Build the tree.
            treeContainer.jstree({
              core: {
                'check_callback' : function (operation, node, node_parent, node_position, more) {
                  return true;
                },
                data: {
                  url: function(node) {
                    return node.id === '#' ?
                        dataURL :
                        dataURL;
                  },
                  data: function(node) {
                    return node;
                  }
                },
                themes: themes,
                "multiple": false,
              },
              'dnd' : {
                'copy': false,
                'is_draggable' : function(node) {
                  let can_drag = node[0].data.draggable;
                  if (can_drag) {
                    return true;
                  }
                  else {
                    let drupalMessages = new Drupal.Message();
                    drupalMessages.clear();
                    drupalMessages.add(Drupal.t("Cannot drag this item, possibly because it has multiple parents or ancestors."), {type: 'warning'});
                    return false;
                  }
                }
              },
              search: {
                show_only_matches: true,
                "search_callback": function(str, node) {
                  //search for any of the words entered
                  var word, words = [];
                  var searchFor = str.toLowerCase().replace(/^\s+/g, '').replace(/\s+$/g, '');
                  if (searchFor.indexOf(' ') >= 0) {
                    words = searchFor.split(' ');
                  } else {
                    words = [searchFor];
                  }
                  for (var i = 0; i < words.length; i++) {
                    word = words[i];
                    if ((node.text || "").toLowerCase().indexOf(word) >= 0) {
                      return true;
                    }
                  }
                  return false;
                }
              },
              'sort' : function(a, b) {
                return parseInt(this.get_node(a).data.weight) > parseInt(this.get_node(b).data.weight) ? 1 : -1;
              },
              plugins: plugins
            });

           // Node move event.
            treeContainer.on("move_node.jstree", function(event, data) {
              const thisTree = data.instance;
              const movedNode = data.node;
              const parent = data.parent === '#' ? 0 : data.parent;
              const parent_node = thisTree.get_node(data.parent);
              const old_parent = data.old_parent === '#' ? 0 : data.old_parent;
              const drupalMessages = new Drupal.Message();

              if (!rollback) {
                let parentText = Drupal.t('root');
                if (parent !== 0) {
                  parentText = $("<div/>").html(thisTree.get_node(parent).text);
                  parentText.find("span").remove();
                  parentText = parentText.text();
                }
                // Function to move the tree item.
                function moveTreeItem() {
                  // Update the data on server side.
                  $.post(updateURL, {
                    keys: [movedNode.id],
                    target: data.position,
                    parent: parent,
                    old_parent: old_parent,
                    old_position: data.old_position
                  })
                    .done(function(response) {
                      if (response.result !== "success") {
                        alert("Server error:" + response.result);
                        rollback = true;
                        thisTree.move_node(movedNode, data.old_parent, data.old_position);
                      }
                      else {
                        if (parent_node.data && !parent_node.data.draggable) {
                          // The parent node is not draggable.
                          // We have to update all duplicated nodes
                          // by refreshing the whole tree.
                          thisTree.refresh();
                        }
                        else {
                          // Update the nodes changed in the server side.
                          if (response.updated_nodes) {
                            let update_nodes = response.updated_nodes;
                            for (const id in update_nodes) {
                              let node = thisTree.get_node(id);
                              if (node) {
                                node.data.weight = update_nodes[id];
                              }
                            }
                            //Refresh the tree without reloading data from server.
                            thisTree.sort(parent_node, true);
                            thisTree.redraw(true);
                          }
                        }
  
                        let message = Drupal.t('@node is moved to position @position under @parent', {'@node': data.node.text, '@parent': parentText, '@position': data.position + 1});
                        // Inform user the movement.
                        drupalMessages.clear();
                        drupalMessages.add(message);
                      }
                    })
                    .fail(function() {
                      drupalMessages.clear();
                      drupalMessages.add(Drupal.t("Can't connect to the server."), {type: 'error'});
                      rollback = true;
                      thisTree.move_node(movedNode, data.old_parent, data.old_position);
                    });
                }

                // Check if confirmation dialog is enabled.
                if (typeof confirm !== 'undefined' && confirm !== false) {
                  // Confirmation dialog enabled.
                  let modalTitle = Drupal.t('Confirm move?');
                  let modalMessage = Drupal.t('Move <em class="placeholder">@node</em> to position @position under <em class="placeholder">@parent</em>?', { '@node': data.node.text, '@parent': parentText, '@position': data.position + 1 });
                  modalConfirmation(modalTitle, modalMessage, moveTreeItem, function () {
                    // Callback when confirmation is denied.
                     rollback = true;
                     thisTree.move_node(movedNode, data.old_parent, data.old_position);
                   });
                } else {
                  // Confirmation dialog disabled.
                  moveTreeItem()
                }

              }
              else {
                rollback = false;
              }
            });

            treeContainer.on('ready.jstree open_node.jstree move_node.jstree search.jstree clear_search.jstree redraw.jstree', function (event, data) {
              Drupal.attachBehaviors(event.target);
            });

            // Search filter box.
            let to = false;
            $(searchTextID).keyup(function() {
              const searchInput = $(this);
              if (to) {
                clearTimeout(to);
              }
              to = setTimeout(function() {
                const v = searchInput.val();
                treeContainer.jstree(true).search(v);
              }, 250);
            });
          }
        });
    }
  };

  /**
   * Generic modal helper function.
   *
   * @param {string} title - The title for the confirm dialog.
   * @param {string} message - The main message for the confirm dialog.
   * @param {function} accept - Callback fired when the user answers positive.
   * @param {function} deny - Callback fired when the user answers negative.
   * @returns {Object} - A jQuery dialog object.
   */
  function modalConfirmation(title, message, accept, deny) {
    let proceed = false;
    let modalConfirmationForm = $('<div></div>').appendTo('body')
      .html(message)
      .dialog({
        modal: true,
        title: title,
        autoOpen: false,
        width: 400,
        resizable: false,
        sticky: true,
        closeOnEscape: true,
        dialogClass: "hm-confirm",
        buttons: [
          {
            class: 'button button--primary',
            text: Drupal.t('Yes'),
            click: function () {
              proceed = true;
              $(this).dialog('close');
            }
          },
          {
            class: 'button',
            text: Drupal.t('No'),
            click: function () {
              $(this).dialog('close');
            }
          }
        ],
        close: function () {
          proceed ? accept() : deny();
        }
      });
    return modalConfirmationForm.dialog('open');
  }

})(jQuery, Drupal, once);
