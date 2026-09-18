export const app = () => document.querySelector('.acorn-hc__app');
export const escapeHtml = value => { const node=document.createElement('div'); node.textContent=String(value??''); return node.innerHTML; };
export const progress = (current,total) => `<p class="acorn-hc__progress-text">Question ${current} of ${total}</p><progress value="${current}" max="${total || 1}">${current} of ${total}</progress>`;
export function focusHeading(){ const heading=app().querySelector('h1,h2'); if(heading){heading.tabIndex=-1;heading.focus();} }
export function error(message){ return `<p class="acorn-hc__error" role="alert" tabindex="-1">${escapeHtml(message)}</p>`; }
