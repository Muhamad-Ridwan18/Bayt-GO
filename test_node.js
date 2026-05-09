const fs = require('fs');
const code = fs.readFileSync('list.js', 'utf8');
const script = `
  const module = {};
  const exports = {};
  const window = {};
  const globalThis = window;
  ${code}
  console.log(Object.keys(window));
`;
fs.writeFileSync('test.js', script);
