import {default as Modal} from '@typo3/backend/modal.js';
import Severity from '@typo3/backend/severity.js';
import RegularEvent from '@typo3/core/event/regular-event.js';

class DshMastodonBackend {
  constructor() {
    new RegularEvent('click', (e) => {
      e.preventDefault();
      this.deletePost(e.target);
    }).delegateTo(document, '.js-delete-confirm');
    new RegularEvent('click', (e) => {
      e.preventDefault();
      this.performAction(e.target);
    }).delegateTo(document, '.js-publish');
    new RegularEvent('click', (e) => {
      e.preventDefault();
      this.unpublishPost(e.target);
    }).delegateTo(document, '.js-unpublish-confirm');
    new RegularEvent('click', (e) => {
      e.preventDefault();
      this.openNewApplication(e.target);
    }).delegateTo(document, '.js-new-application');
  }

  deletePost(linkOrButton) {
    const title = TYPO3.lang['delete.confirm.title'] || 'Delete this record?';
    let content = (TYPO3.lang['delete.confirm.content'] || 'Are you sure you want to delete the record?');
    const modal = Modal.confirm(title, content, Severity.warning, [
      {
        text: TYPO3.lang['delete.confirm.button.cancel'] || 'Cancel',
        btnClass: 'btn-default',
        name: 'cancel'
      },
      {
        text: TYPO3.lang['delete.confirm.button.ok'] || 'Yes, delete this record',
        btnClass: 'btn-warning',
        name: 'ok',
        active: true
      }
    ]);
    modal.addEventListener('confirm.button.ok', () => {
      modal.hideModal();
      this.performAction(linkOrButton);
    });
    modal.addEventListener('confirm.button.cancel', function () {
      modal.hideModal();
    });
  }

  unpublishPost(linkOrButton) {
    const title = TYPO3.lang['unpublish.confirm.title'] || 'Unpublish this post?';
    let content = (TYPO3.lang['unpublish.confirm.content'] || 'Are you sure you want to unpublish the post?');
    const modal = Modal.confirm(title, content, Severity.warning, [
      {
        text: TYPO3.lang['delete.confirm.button.cancel'] || 'Cancel',
        btnClass: 'btn-default',
        name: 'cancel'
      },
      {
        text: TYPO3.lang['unpublish.confirm.button.ok'] || 'Yes, unpublish this post',
        btnClass: 'btn-warning',
        name: 'ok',
        active: true
      }
    ]);
    modal.addEventListener('confirm.button.ok', () => {
      modal.hideModal();
      this.performAction(linkOrButton);
    });
    modal.addEventListener('confirm.button.cancel', function () {
      modal.hideModal();
    });
  }

  performAction(linkOrButton) {
    if (linkOrButton.tagName === 'A') {
      let form = document.createElement('form');
      form.action = linkOrButton.href;
      form.method = 'post';
      document.body.appendChild(form);
      form.submit();
    } else {
      linkOrButton.form.submit();
    }
  }

  openNewApplication(linkOrButton) {
    let form = linkOrButton.closest('form');
    if (!form) {
      return;
    }
    let domain = form.querySelector('input[name=domain]');
    if (!domain || !domain.value) {
      return;
    }
    window.open('https://' + domain.value + '/settings/applications/new', '_blank');
  }
}

export default new DshMastodonBackend();
