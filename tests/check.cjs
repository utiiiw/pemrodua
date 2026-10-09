const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const html = fs.readFileSync(path.join(__dirname, '..', 'index.html'), 'utf8');
const script = [...html.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/g)].map(match => match[1]).join('\n');
const context = vm.createContext({ document: { addEventListener() {} }, URL, setTimeout, clearTimeout });
vm.runInContext(script, context);
const evaluate = expression => vm.runInContext(expression, context);

assert.equal(evaluate('initialPosts.length'), 8);
assert.equal(evaluate("evaluateLocalSentiment('Terima kasih bantuan gratis').sentiment"), 'Positif');
assert.equal(evaluate("evaluateLocalSentiment('Mata perih dan batuk parah').sentiment"), 'Negatif');
assert.equal(evaluate("evaluateLocalSentiment('Informasi pengamatan hari ini').sentiment"), 'Netral');
assert.equal(evaluate('percentage(0, 0)'), '0,0%');
assert.equal(evaluate("escapeHtml('<img src=x onerror=alert(1)>')"), '&lt;img src=x onerror=alert(1)&gt;');
assert.equal(evaluate("safeSourceUrl('javascript:alert(1)')"), '');
assert.equal(evaluate("safeSourceUrl('https://user:password@example.com/')"), '');
assert.equal(evaluate("safeSourceUrl('https://example.com/comment/1')"), 'https://example.com/comment/1');
assert.equal(evaluate("csvCell('a,\"b\"\\nc')"), '"a,""b""\nc"');
assert.equal(evaluate("csvCell('=1+1')"), '"\'=1+1"');
assert.equal(evaluate("csvCell('  =1+1')"), '"\'  =1+1"');
assert.equal(evaluate("csvCell('@someone')"), '"\'@someone"');
assert.equal(evaluate("csvCell(null)"), '""');
assert.doesNotMatch(html, /apify_api_[A-Za-z0-9]{20,}/);
console.log('Dashboard logic, HTML escaping, safe URLs and CSV checks passed.');
