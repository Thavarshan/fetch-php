import { onMounted, onBeforeUnmount } from 'vue';
import { createFocusTrap, type FocusTrap } from 'focus-trap';

// Adapt the existing VitePress menus; retain its controls and route handling.
export function useMenuFocus(): void {
    let observer: MutationObserver | undefined;
    let search: { trigger: HTMLElement; path: string } | undefined;
    let searchTrigger: HTMLElement | undefined;
    let restoreFrame = 0;
    let current: {
        panel: HTMLElement; trigger: HTMLElement; trap: FocusTrap;
        path: string; tabIndex: string | null;
    } | undefined;

    function release(restore = true): void {
        if (!current) return;
        const { panel, trigger, trap, path, tabIndex } = current;
        current = undefined;
        trap.deactivate({ returnFocus: restore && path === location.pathname
            && trigger.isConnected && trigger.offsetWidth > 0 });
        if (tabIndex === null) panel.removeAttribute('tabindex');
        else panel.setAttribute('tabindex', tabIndex);
    }

    function refresh(): void {
        const searching = document.querySelector('.VPLocalSearchBox');
        if (searching && !search) {
            const trigger = searchTrigger
                || document.querySelector<HTMLElement>(
                '.DocSearch-Button');
            if (trigger) search = { trigger, path: location.pathname };
        } else if (!searching && search) {
            const previous = search;
            search = undefined;
            restoreFrame = requestAnimationFrame(() => {
                if (previous.path === location.pathname) {
                    previous.trigger.focus();
                }
            });
        }
        const sidebar = innerWidth < 960
            ? document.querySelector<HTMLElement>('.VPSidebar.open') : null;
        const screen = innerWidth < 768
            ? document.getElementById('VPNavScreen') : null;
        const panel = sidebar || screen;
        if (!panel) {
            release();
            return;
        }
        if (current?.panel === panel) {
            if (document.querySelector('.VPLocalSearchBox')) {
                current.trap.pause();
            } else if (current.trap.paused) current.trap.unpause();
            return;
        }
        release(false);
        const trigger = document.querySelector<HTMLElement>(sidebar
            ? '.VPLocalNav .menu' : '.VPNavBarHamburger');
        if (!trigger) return;
        const tabIndex = panel.getAttribute('tabindex');
        panel.setAttribute('tabindex', '-1');
        const trap = createFocusTrap(panel, {
            fallbackFocus: panel,
            setReturnFocus: trigger,
            escapeDeactivates: false,
            allowOutsideClick: true,
            preventScroll: true,
        });
        current = { panel, trigger, trap, path: location.pathname, tabIndex };
        trap.activate();
    }

    function onClick(event: MouseEvent): void {
        const target = event.target as Element;
        const button = target.closest<HTMLElement>('.DocSearch-Button');
        if (button) searchTrigger = button;
    }

    function onKeydown(event: KeyboardEvent): void {
        if (event.key === 'Escape' && current?.panel.id === 'VPNavScreen') {
            current.trigger.click();
            event.preventDefault();
        }
        // VitePress already handles Escape for the documentation sidebar.
    }

    onMounted(() => {
        observer = new MutationObserver(refresh);
        observer.observe(document.body, {
            subtree: true, childList: true,
            attributes: true, attributeFilter: ['class', 'aria-expanded'],
        });
        document.addEventListener('keydown', onKeydown);
        document.addEventListener('click', onClick, true);
        window.addEventListener('resize', refresh);
        refresh();
    });
    onBeforeUnmount(() => {
        observer?.disconnect();
        cancelAnimationFrame(restoreFrame);
        document.removeEventListener('click', onClick, true);
        document.removeEventListener('keydown', onKeydown);
        window.removeEventListener('resize', refresh);
        release(false);
    });
}
