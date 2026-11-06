import '../css/app.css';
import Alpine from 'alpinejs';
import store from './store';
import * as utils from './lib/utils';
import { subscribeRealtime } from './lib/realtime';

// Each file in ./alpine registers its Alpine.data() components (one file per React component it replaces).
const modules = import.meta.glob('./alpine/*.js', { eager: true });

Alpine.store('kb', store);
Alpine.magic('u', () => utils);
Alpine.magic('rt', () => subscribeRealtime);
for (const m of Object.values(modules)) m.default?.(Alpine);

window.Alpine = Alpine;
Alpine.start();
