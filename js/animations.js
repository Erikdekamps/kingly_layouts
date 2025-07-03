/**
 * @file
 * Kingly Layouts animation behaviors.
 */

(function ($, window, Drupal, once) {
  /**
   * Attaches the animation-on-scroll behavior.
   *
   * This script uses the IntersectionObserver API to add a 'is-visible' class
   * to elements with the '.kl-animate' class when they enter the viewport.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for Kingly Layout animations.
   */
  Drupal.behaviors.kinglyAnimations = {
    /**
     * Behavior attach function.
     *
     * @param {HTMLElement} context
     *   The DOM element to which this behavior is being attached.
     * @param {object} settings
     *   An object containing the current system settings.
     */
    attach(context, settings) {
      // Use once() to ensure the behavior is attached only once per element.
      const animatedElements = once('kingly-animation', '.kl-animate', context);

      if (!animatedElements.length) {
        return;
      }

      // Fallback for browsers that don't support IntersectionObserver.
      // The elements will simply be visible by default.
      if (!('IntersectionObserver' in window)) {
        animatedElements.forEach(element => element.classList.add('is-visible'));
        return;
      }

      // Set up the Intersection Observer.
      const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
          // If the element is in the viewport, add the 'is-visible' class.
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            // Stop observing the element after it has been animated once.
            // This prevents re-triggering the animation on scroll up/down.
            obs.unobserve(entry.target);
          }
        });
      }, {
        // Options: start triggering when 15% of the element is visible.
        threshold: 0.15,
      });

      // Observe each animated element.
      animatedElements.forEach(element => {
        observer.observe(element);
      });
    },
  };
})(jQuery, window, Drupal, once);
