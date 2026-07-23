/**
 * @file
 * Attach the JavaScript to responsive images to load the best image style.
 */
(function (Drupal) {
  'use strict';

  Drupal.behaviors.easyResponsiveImages = {
    attach: function (context) {
      // Fetch all images containing a "data-srcset" attribute.
      const images = context.querySelectorAll('img[data-srcset]');

      // Return early if no images were found.
      if (!images || images.length === 0) {
        return;
      }

      // Define an offset to preload lazy loaded images before they are visible
      // in the viewport.
      const preloadOffset = 300;

      // Find the best suitable image to display.
      const updateImage = function (image) {
        // When an image is already loading, we need to wait until loading has
        // finished. Different observers might try to update the image
        // simultaneously.
        if (image.hasAttribute('data-loading')) {
          setTimeout(function () {
            updateImage(image);
          }, 100);
          return;
        }

        // Check if we need to apply a multiplier to the width check and load
        // bigger images.
        let multiplier = 1;
        const dataMultiplier = image.getAttribute('data-multiplier');
        if (dataMultiplier !== null) {
          multiplier = parseFloat(dataMultiplier.replace('x', ''));
        }
        const multiplierMax = multiplier >= 1 ? multiplier : 1;
        const multiplierFactor = window.devicePixelRatio < multiplierMax ? window.devicePixelRatio : multiplierMax;

        // Check if we need to use the container height to select the best
        // image.
        const cover = image.hasAttribute('data-cover');

        // Check the available width for the image based on the multiplier.
        const imgWidth = Math.floor(image.clientWidth);
        const imgHeight = Math.floor(image.clientHeight);
        const parentWidth = Math.floor(image.parentNode.clientWidth);
        const parentHeight = Math.floor(image.parentNode.clientHeight);
        const availableWidth = parentWidth > imgWidth ? parentWidth : imgWidth;
        const availableHeight = parentHeight > imgHeight ? parentHeight : imgHeight;
        const targetWidth = multiplierFactor * availableWidth;
        const targetHeight = multiplierFactor * availableHeight;

        // Get the current width and height of the image.
        const attrWidth = image.getAttribute('width');
        const attrHeight = image.getAttribute('height');

        // Get all the source values and the current image source.
        const sources = image.getAttribute('data-srcset').split(',');
        const currentSrc = image.getAttribute('src');

        // Fetch the image ratio if it is available.
        let ratioWidth, ratioHeight;
        if (image.hasAttribute('data-ratio')) {
          [ratioWidth, ratioHeight] = image.getAttribute('data-ratio').split(':');
        }

        // If the selected image has a src attribute, and is already bigger than
        // the available width, we do not update the image.
        if (image.src && attrWidth && attrWidth > availableWidth && attrHeight && attrHeight > availableHeight) {
          image.setAttribute('data-loaded', 1);
          return;
        }

        // Find the best matching source based on actual image space.
        let source, responsiveImgPath, responsiveImgWidth, responsiveImgHeight;
        for (source of sources) {
          let array = source.split(' ');
          responsiveImgPath = array[0];
          responsiveImgWidth = array[1].slice(0, -1);
          if (cover && parentWidth && parentHeight) {
            responsiveImgHeight = (responsiveImgWidth * parentWidth) / parentHeight;
          }
          else if (ratioWidth && ratioHeight) {
            responsiveImgHeight = (responsiveImgWidth * ratioHeight) / ratioWidth;
          }
          else if (attrWidth && attrHeight) {
            responsiveImgHeight = (responsiveImgWidth * attrHeight) / attrWidth;
          }
          // Stop looking if the image is bigger than the target width and the
          // image height is bigger than the target height.
          if (targetWidth < responsiveImgWidth && (!cover || !responsiveImgHeight || targetHeight < responsiveImgHeight)) {
            break;
          }
        }

        // If the image is already loaded, we do not update the image.
        if (responsiveImgPath === currentSrc) {
          image.setAttribute('data-loaded', 1);
          return;
        }

        // Set a temporary attribute to prevent multiple observers from updating
        // the image simultaneously.
        image.setAttribute('data-loading', 1);

        // Update the "src" with the new image path.
        image.src = responsiveImgPath;

        // Calculate the height from the dimensions of the initial width and
        // height since this can also cause layout shifts.
        image.setAttribute('width', availableWidth);
        if (cover && parentWidth && parentHeight) {
          image.setAttribute('height', Math.round((availableWidth * parentHeight) / parentWidth));
        }
        else if (ratioWidth && ratioHeight) {
          image.setAttribute('height', Math.round((availableWidth * ratioHeight) / ratioWidth));
        }
        else if (attrWidth && attrHeight) {
          image.setAttribute('height', Math.round((availableWidth * attrHeight) / attrWidth));
        }

        // Set the "data-loaded" attribute when the image is loaded.
        image.onload = function () {
          this.setAttribute('data-loaded', 1);

          // Remove the temporary attribute when the image finishes loading.
          image.removeAttribute('data-loading');

          // Update the image width/height onload to the actual image
          // dimensions.
          if (cover || !ratioWidth || !ratioHeight) {
            this.setAttribute('width', Math.floor(this.width));
            this.setAttribute('height', Math.floor(this.height));
          }
        };
      };

      // Create a ResizeObserver to update the image "src" attribute when its
      // parent container resizes.
      const resizeObserver = new ResizeObserver((entries) => {
        for (let entry of entries) {
          const images = entry.target.querySelectorAll('img[data-srcset]');
          images.forEach((image) => {
            updateImage(image);
          });
        }
      });

      // Create an IntersectionObserver to update the image "src" attribute when
      // it is visible in the viewport.
      const intersectionObserver = new IntersectionObserver((entries) => {
        for (let entry of entries) {
          if (entry.isIntersecting) {
            // Remove the IntersectionObserver when the image is visible.
            intersectionObserver.unobserve(entry.target);

            // Load the correct image.
            updateImage(entry.target);

            // Attach the ResizeObserver to the image container.
            resizeObserver.observe(entry.target.parentNode);
          }
        }
      }, {rootMargin: preloadOffset + 'px'});

      // Attach the ResizeObserver and IntersectionObserver to the images.
      images.forEach((image) => {
        // Make sure we only attach the observers once.
        if (image.hasAttribute('data-observed')) {
          return;
        }

        // Mark that the image the observers are attached.
        image.setAttribute('data-observed', '1');

        // Attach the IntersectionObserver to the image if it is using lazy
        // loading, otherwise attach the ResizeObserver.
        if(image.hasAttribute('loading') && image.getAttribute('loading') === 'lazy') {
          // When an image is using lazy loading, we unset the src to prevent it
          // from being loaded directly. This prevents the browser from lazy
          // loading the default image before we get the chance to set the
          // correct src.
          image.removeAttribute('src');

          // Attach the IntersectionObserver to the image.
          intersectionObserver.observe(image);
        } else {
          // Load the correct image directly.
          updateImage(image);

          // Attach the ResizeObserver to the image container.
          resizeObserver.observe(image.parentNode);
        }
      });
    },
  };

})(Drupal);
