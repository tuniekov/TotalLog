import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const modulesDir = __dirname;
const files = fs.readdirSync(modulesDir)
  .filter(file => file.endsWith('.vue'))
  .map(file => file.replace('.vue', ''));

const imports = files.map(name => `import ${name} from './${name}.vue'`).join('\n');
const exports = files.map(name => `    ${name}`).join(',\n');

const content = `// Автоматический экспорт всех модулей
${imports}

export const modules = {
${exports}
}

// Список доступных модулей для селекта
export const modulesList = Object.keys(modules).map(name => ({ module: name }))
`;

fs.writeFileSync(path.join(modulesDir, 'index.js'), content);
console.log('index.js обновлен с модулями:', files.join(', '));
