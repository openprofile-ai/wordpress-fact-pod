(function () {
    function normalizeLoginFields(container) {
        container.querySelectorAll('.form-row').forEach(row => {
            const label = row.querySelector('label');
            const input = row.querySelector('input[type="password"], input[type="email"], input[type="text"], textarea');

            if (label && input) {
                if (label.compareDocumentPosition(input) & Node.DOCUMENT_POSITION_FOLLOWING) {
                    row.insertBefore(input, label);
                    row.appendChild(label);
                }

                if (!input.getAttribute('placeholder')) {
                    input.setAttribute('placeholder', ' ');
                }

                row.classList.add('fp-float');
                input.classList.add('fp-input');
                label.classList.add('fp-label');
            }
        });

        container.querySelectorAll('#oauth-scopes-form label').forEach(l => {
            l.style.pointerEvents = 'auto';
            l.style.cursor = 'pointer';
        });
    }

    function injectShadow(host) {
        if (!host || host.shadowRoot) return;

        const originalHtml = host.innerHTML;
        const shadow = host.attachShadow({ mode: 'open' });

        const shell = document.createElement('div');
        shell.className = 'wrapper';
        shell.innerHTML = `<div class="box">${originalHtml}</div>`;

        const baseCSS = `
:host {
  all: initial;
  font-family: 'Open Sans', system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Arial, sans-serif;
  color:#202124;
}
.wrapper {
  position: fixed;
  inset: 0;
  display: grid;
  place-items: center;
  background: #f2f2f2;
}
.box {
  width: min(92vw, 380px);
  padding: 32px 32px 28px;
  border: 1px solid #dadce0;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 1px 2px rgba(60,64,67,.30), 0 1px 3px 1px rgba(60,64,67,.15);
}
.logo {
  display: flex;
  justify-content: center;
  margin-bottom: 12px;
}
.box h2 {
  margin: 6px 0 4px;
  font-size: 22px;
  font-weight: 500;
  text-align: center;
  color: #202124;
}
.box p {
  margin: 0 0 18px;
  text-align: center;
  color: #5f6368;
  font-size: 14px;
}

.form-row { margin: 0 0 16px; position: relative; }

.button, button, input[type="submit"] {
  appearance: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 16px;
  font-size: 14px;
  font-weight: 600;
  color: #fff;
  background: #1a73e8;
  border: 0;
  border-radius: 4px;
  cursor: pointer;
  text-decoration: none;
  box-shadow: 0 1px 1px rgba(66,133,244,.40), 0 1px 3px 1px rgba(66,133,244,.25);
  transition: background .15s ease, transform .02s ease;
}
.button:hover, button:hover, input[type="submit"]:hover { background: #287ae6; }
a { color:#1a73e8; text-decoration: none; }
a:hover { text-decoration: underline; }

.woocommerce-error, .woocommerce-message {
  margin: 0 0 12px;
  padding: 10px 12px;
  border-radius: 4px;
  font-size: 13px;
}
.woocommerce-error { background:#fce8e6; color:#d93025; }
.woocommerce-message { background:#e6f4ea; color:#137333; }

#oauth-scopes-form .scope {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  margin-bottom: 8px;
  font-size: 14px;
  color: #3c4043;
}
#oauth-scopes-form .scope input[type="checkbox"] { margin-top: 2px; }
.actions { display:flex; gap:12px; justify-content:flex-end; margin-top:8px; }
`;

        const styleBase = document.createElement('style');
        styleBase.textContent =
            `@import url('https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap');\n` +
            baseCSS;

        const styleApp = document.createElement('style');
        const cssUrl = (typeof FACTPOD === 'object' && FACTPOD.cssUrl) ? FACTPOD.cssUrl : null;

        shadow.appendChild(styleBase);
        shadow.appendChild(styleApp);
        shadow.appendChild(shell);

        normalizeLoginFields(shadow);

        if (cssUrl) {
            fetch(cssUrl, { credentials: 'same-origin' })
                .then(r => (r.ok ? r.text() : Promise.reject(new Error('Failed to load CSS'))))
                .then(cssText => { styleApp.textContent = cssText; })
                .catch(err => { console.warn('[FactPod] Could not load CSS from', cssUrl, err); });
        } else {
            console.warn('[FactPod] cssUrl is not provided.');
        }

        host.innerHTML = '';
    }

    function init() {
        const selectors =
            (typeof FACTPOD === 'object' && Array.isArray(FACTPOD.hosts) && FACTPOD.hosts.length)
                ? FACTPOD.hosts
                : ['#factpod-root', '[data-factpod]'];

        selectors
            .flatMap(s => Array.from(document.querySelectorAll(s)))
            .forEach(injectShadow);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
