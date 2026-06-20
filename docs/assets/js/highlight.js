
(() => {
  const html = (s) => String(s).replace(/[&<>]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[ch]));
  const span = (cls, s) => `<span class="${cls}">${html(s)}</span>`;

  const phpKeywords = new Set([
    'function','class','namespace','use','return','public','private','protected','static','final','extends','implements',
    'new','if','else','elseif','foreach','for','while','try','catch','throw','true','false','null','array','string',
    'int','integer','bool','boolean','float','mixed','void','callable','fn','const','readonly','declare','strict_types',
    'switch','case','default','break','continue','yield','match','interface','trait','abstract','self','parent'
  ]);

  function highlightPhp(text) {
    const re = /<\?php|\?>|\/\/[^\n]*|#[^\n]*|\/\*[\s\S]*?\*\/|'(?:\\.|[^'\\])*'|"(?:\\.|[^"\\])*"|\$[A-Za-z_][A-Za-z0-9_]*|\b\d+(?:\.\d+)?\b|\b[A-Za-z_][A-Za-z0-9_\\]*\b|::|->|=>|[{}\[\]();,.=]/g;
    let out = '';
    let last = 0;
    let m;
    while ((m = re.exec(text)) !== null) {
      const token = m[0];
      out += html(text.slice(last, m.index));
      if (token === '<?php' || token === '?>') out += span('tok-tag', token);
      else if (token.startsWith('//') || token.startsWith('#') || token.startsWith('/*')) out += span('tok-comment', token);
      else if (token.startsWith("'") || token.startsWith('"')) out += span('tok-string', token);
      else if (token.startsWith('$')) out += span('tok-var', token);
      else if (/^\d/.test(token)) out += span('tok-number', token);
      else if (phpKeywords.has(token)) out += span('tok-keyword', token);
      else if (/^[A-Z][A-Za-z0-9_\\]*$/.test(token)) out += span('tok-class', token);
      else if (/^(::|->|=>|[{}\[\]();,.=])$/.test(token)) out += span('tok-symbol', token);
      else {
        const next = text.slice(re.lastIndex).match(/^\s*\(/);
        out += next ? span('tok-function', token) : html(token);
      }
      last = re.lastIndex;
    }
    out += html(text.slice(last));
    return out;
  }

  function highlightConfig(text) {
    return text.split('\n').map(line => {
      const raw = line;
      if (/^\s*#/.test(raw)) return span('tok-comment', raw);
      const m = raw.match(/^(\s*)([A-Za-z0-9_.\-\/]+)(\s*=\s*|\s+)/);
      if (m) {
        const rest = raw.slice(m[0].length);
        return html(m[1]) + span('tok-keyword', m[2]) + span('tok-symbol', m[3]) + html(rest).replace(/(&quot;.*?&quot;|'.*?')/g, '<span class="tok-string">$1</span>');
      }
      return html(raw);
    }).join('\n');
  }

  function highlightHtml(text) {
    const re = /<!--[\s\S]*?-->|<\/?[A-Za-z][^>]*>|"[^"]*"|'[^']*'/g;
    let out = '';
    let last = 0;
    let m;
    while ((m = re.exec(text)) !== null) {
      const token = m[0];
      out += html(text.slice(last, m.index));
      if (token.startsWith('<!--')) out += span('tok-comment', token);
      else if (token.startsWith('<')) out += span('tok-tag', token);
      else out += span('tok-string', token);
      last = re.lastIndex;
    }
    out += html(text.slice(last));
    return out;
  }

  window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('pre code').forEach(code => {
      const text = code.textContent;
      const cls = code.className || '';
      if (cls.includes('language-html') || cls.includes('language-xml')) code.innerHTML = highlightHtml(text);
      else if (cls.includes('language-bash') || cls.includes('language-nginx') || cls.includes('language-apache') || cls.includes('language-dotenv') || cls.includes('language-ini') || cls.includes('language-json') || cls.includes('language-text')) code.innerHTML = highlightConfig(text);
      else code.innerHTML = highlightPhp(text);
    });
  });
})();
