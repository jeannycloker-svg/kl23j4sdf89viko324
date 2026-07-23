((Drupal, once) => {
  Drupal.behaviors.ginLbToolbar = {
    attach: (context) => {
      once('glb-primary-save', '.js-glb-primary-save', context).forEach(
        (item) => {
          const primary_button = document.querySelector('#gin_sidebar .form-actions .js-glb-button--primary');
          if (primary_button !== null && primary_button !== undefined) {
            if (primary_button.getAttribute('disabled') === 'disabled') {
              item.setAttribute('disabled', 'disabled');
              item.classList.add('is-disabled');
            }
            else {
              item.addEventListener('click', () => {
                primary_button.click();
              });
            }
          }
        },
      );
    },
  };
})(Drupal, once);
