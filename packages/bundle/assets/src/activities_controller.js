import { Controller } from '@hotwired/stimulus';

const TWIG_COMPONENT = 'twig_component';

/**
 * Shows the agent components the agent renders: the HTML of each `twig_component`
 * activity goes in its activity slot (`[data-ag-ui-activity="<messageId>"]`) anywhere in
 * the client controller's element, or is appended to this element when there is none.
 * Stimulus connects the controllers in that HTML, Live Components included.
 *
 * It must sit inside the client controller's element, whose events it listens to.
 *
 * The HTML is trusted as the server's: Twig rendered it, escaping the props the model
 * filled. An endpoint that lets another producer send `twig_component` activities must
 * not use this controller.
 */
export default class extends Controller {
    connect() {
        this.client = this.element.closest('[data-controller~="ag-ui"]');
        if (!this.client) {
            throw new Error('The ag-ui-activities controller must sit inside the ag-ui controller\'s element.');
        }

        this.listener = (event) => this.render(event.detail);
        this.client.addEventListener('ag-ui:activity-changed', this.listener);
    }

    disconnect() {
        this.client?.removeEventListener('ag-ui:activity-changed', this.listener);
    }

    render({ messageId, activityType, content }) {
        if (activityType !== TWIG_COMPONENT) return;

        const slot = Array.from(this.client.querySelectorAll('[data-ag-ui-activity]')).find((element) => element.dataset.agUiActivity === messageId)
            ?? this.append(messageId);
        if (content.interruptId) slot.dataset.agUiInterruptId = content.interruptId;
        slot.innerHTML = content.html;
    }

    append(messageId) {
        const slot = document.createElement('div');
        slot.dataset.agUiActivity = messageId;
        this.element.append(slot);
        return slot;
    }
}
