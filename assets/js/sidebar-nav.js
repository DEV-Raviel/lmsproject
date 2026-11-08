const mainContent = document.querySelector('main.main-content, .main-content');
if (mainContent && !mainContent.id) {
    mainContent.id = 'main-content';
    mainContent.tabIndex = -1;
}

const activeSidebarLink = document.querySelector('.sidebar ul a.active');
if (activeSidebarLink && window.matchMedia('(max-width: 768px)').matches) {
    activeSidebarLink.scrollIntoView({block: 'nearest', inline: 'center'});
}
