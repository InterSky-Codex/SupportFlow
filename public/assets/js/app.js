class SidebarController {
    constructor() {
        this.sidebar = document.querySelector('#app-sidebar');
        this.trigger = document.querySelector('[data-sidebar-toggle]');

        this.bindEvents();
    }

    bindEvents() {
        if (!this.sidebar || !this.trigger) {
            return;
        }

        this.trigger.addEventListener('click', () => {
            this.sidebar.classList.toggle('is-open');
        });
    }
}

document.addEventListener('DOMContentLoaded', () => new SidebarController());
