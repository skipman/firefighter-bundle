function animateFirefighterItems(firefighterfilters, firefighterItems, firefighterduration) {
  let itemCategory;
  let animationDuration = firefighterduration || 500;
  let activeFilter;

  firefighterfilters.forEach((filter) => {
    if(filter.classList.contains('active')) {
      activeFilter = filter;
    }

    filter.addEventListener('click', (e) => {
      e.preventDefault();

      if(activeFilter) {
        activeFilter.classList.remove('active');
      }

      e.target.classList.add('active');
      activeFilter = e.target;

      let filterFirefighterCategory = activeFilter.dataset.category;

      if (filterFirefighterCategory == 'all') {
        firefighterItems.forEach((item) => {
          item.classList.remove('firefighter-visible');

          fadeIn(item, animationDuration);
        });
      } else {
        for (const item of firefighterItems) {
          itemCategory = item.dataset.category;

          if (itemCategory.includes(filterFirefighterCategory)) {
            fadeOut(item, animationDuration).then(function() {
              fadeIn(item, animationDuration);
            });
          } else {
            fadeOut(item, animationDuration);
          }
        }
      }
    });
  });


  function defaultFadeConfig() {
    return {
      easing: 'linear',
      iterations: 1,
      direction: 'normal',
      fill: 'forwards',
      delay: 0,
      endDelay: 0
    }
  }

  function fadeOut(el, animationDuration, config = defaultFadeConfig()) {
    return new Promise((resolve) => {
      const animation = el.animate([
        { opacity: '1' },
        { opacity: '0', offset: 0.5 },
        { opacity: '0', offset: 1 }
      ], {duration: animationDuration, ...config});

      animation.onfinish = () => {
        el.style.display = 'none';
        el.classList.remove('firefighter-visible');
        el.classList.add('firefighter-hidden');
        resolve();
      }
    })
  }

  function fadeIn(el, animationDuration, config = defaultFadeConfig()) {
    return new Promise((resolve) => {
      el.style.display = 'block';

      const animation = el.animate([
        { opacity: '0' },
        { opacity: '0.5', offset: 0.5 },
        { opacity: '1', offset: 1 }
      ], {duration: animationDuration, ...config});


      animation.onfinish = () => {
        el.classList.add('firefighter-visible');
        el.classList.remove('firefighter-hidden');
        resolve();
      }
    });
  }
}
