const activeSidebarLink = document.querySelector('.sidebar ul a.active');
if (activeSidebarLink && window.matchMedia('(max-width: 768px)').matches) {
    activeSidebarLink.scrollIntoView({block: 'nearest', inline: 'center'});
}
