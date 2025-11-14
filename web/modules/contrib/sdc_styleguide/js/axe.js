((Drupal) => {

  'use strict';


  Drupal.theme.validation = (results) => {
    let messages = '<div class="sdc-styleguide-demo__a11y-messages">';
    let groups = '<ul class="styleguide-a11y-evaluation__groups">';
    let types = [
      {
        id: 'violations',
        name: Drupal.t('Violations'),
      },
      {
        id: 'incomplete',
        name: Drupal.t('Warnings'),
      },
      {
        id: 'passes',
        name: Drupal.t('Passes'),
      },
    ];

    for (const type of types) {
      groups += `
        <li class="styleguide-a11y-evaluation__group">
          <button class="styleguide-a11y-evaluation__group-trigger" data-type="${type.id}" aria-controls="a11y-results-${type.id}" aria-expanded="false">${type.name} (${results[type.id].length})</button>
        </li>`
      messages += `
          <ul id="a11y-messages-${type.id}" class="sdc-styleguide-demo__a11y-message-group" data-type="${type.id}">`;
      if (results[type.id].length == 0) {
        messages += `<li class="sdc-styleguide-demo__a11y-message"><span class="sdc-styleguide-demo__a11y-empty">${Drupal.t('Nothing to see here.')}</span></li>`;
      }
      else {
        for (const message of results[type.id]) {
          let elements = [];
          if (type.id != 'passes') {
            for (const node of results[type.id][0].nodes) {
              const div = document.createElement('div');
              const text = document.createTextNode(node.html);
              div.appendChild(text);
              elements.push(`<p>${node.failureSummary}</p><code>${div.innerHTML}</code>`);
            }
          }
          messages += `<li class="sdc-styleguide-demo__a11y-message">
            <div class="sdc-styleguide-demo__a11y-message-trigger" role="button" tabindex="0">
              <div class="sdc-styleguide-demo__a11y-message-summary">
                <a href="${message.helpUrl}" target="_blank" rel="noopener noreferrer">${message.id} ${message.impact ? '(' + message.impact + ')' : ''}</a> <em>${message.help}</em>
              </div>
              <div class="sdc-styleguide-demo__a11y-message-details">
                <p>${message.description}</p>
                ${elements.join('')}
              </div>
            </div>
          </li>`;
        }
      }
      messages += '</ul>';
    }
    messages += '</div>';
    groups += '</ul>';
    return groups + messages;
  };

  Drupal.behaviors.demoValidationInitialization = {
    attach: (context, settings) => {
      const found = once('demo-axe-init', '.styleguide-a11y-evaluation__trigger', context);
      if (found.length == 0) {
        return;
      }

      axe
        .run('.sdc-styleguide-demo', {
          runOnly: ['wcag2a', 'wcag2aa']
        })
        .then(results => {
          document.querySelector('.styleguide-a11y-evaluation__content').innerHTML = Drupal.theme.validation(results);
          const triggers = document.querySelectorAll('.styleguide-a11y-evaluation__group-trigger');
          triggers.forEach(t => {
            t.addEventListener('click', e => {
              triggers.forEach(t2 => {
                t2.setAttribute('aria-expanded', 'false');
              });
              const doExpand = t.getAttribute('aria-expanded') === 'false' ? 'true' : 'false';
              t.setAttribute('aria-expanded', doExpand);
            });
          });
        })
        .catch(err => {
          console.error('Something bad happened:', err.message);
        });

      found[0].addEventListener('click', e => {
        if (!document.querySelector('.styleguide-a11y-evaluation__group-trigger:not([aria-expanded="false"])')) {
          document.querySelector('.styleguide-a11y-evaluation__group-trigger').dispatchEvent(new MouseEvent('click'));
        }
        let doExpand = e.currentTarget.getAttribute('aria-expanded') === 'true' ? 'false' : 'true';
        e.currentTarget.setAttribute('aria-expanded', doExpand);
      });
    }
  };

})(Drupal);