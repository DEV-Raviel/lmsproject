const mainContent = document.querySelector('main.main-content, .main-content');
if (mainContent && !mainContent.id) {
    mainContent.id = 'main-content';
    mainContent.tabIndex = -1;
}

const navigation = document.querySelector('#primary-navigation');
const navigationToggle = document.querySelector('.nav-toggle');
const navigationToggleText = document.querySelector('.nav-toggle-text');
const navigationBackdrop = document.querySelector('.nav-backdrop');
const main = document.querySelector('main.main-content, .main-content');
const mobileNavigationQuery = window.matchMedia('(max-width: 1024px)');

if (navigation && navigationToggle && navigationBackdrop) {
    document.body.classList.add('mobile-nav-ready');
    let navigationOpen = false;

    const updateNavigation = () => {
        const isMobile = mobileNavigationQuery.matches;
        if (!isMobile) {
            navigationOpen = false;
        }

        navigationToggle.setAttribute('aria-expanded', String(isMobile && navigationOpen));
        navigationToggle.setAttribute('aria-label', navigationOpen ? 'Close navigation' : 'Open navigation');
        if (navigationToggleText) {
            navigationToggleText.textContent = navigationOpen ? 'Close' : 'Menu';
        }
        navigation.inert = isMobile && !navigationOpen;
        navigation.setAttribute('aria-hidden', String(isMobile && !navigationOpen));
        navigationBackdrop.hidden = !isMobile || !navigationOpen;
        document.body.classList.toggle('nav-open', isMobile && navigationOpen);

        if (main) {
            main.inert = isMobile && navigationOpen;
        }
    };

    const closeNavigation = (restoreFocus = false) => {
        navigationOpen = false;
        updateNavigation();
        if (restoreFocus) {
            navigationToggle.focus();
        }
    };

    navigationToggle.addEventListener('click', () => {
        navigationOpen = !navigationOpen;
        updateNavigation();
        if (navigationOpen) {
            navigation.querySelector('a.active')?.focus({preventScroll: true});
        }
    });

    navigationBackdrop.addEventListener('click', () => closeNavigation(true));
    navigation.addEventListener('click', event => {
        if (event.target instanceof Element && event.target.closest('a') && mobileNavigationQuery.matches) {
            closeNavigation();
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && navigationOpen) {
            closeNavigation(true);
        }
    });

    mobileNavigationQuery.addEventListener('change', updateNavigation);
    updateNavigation();
}
