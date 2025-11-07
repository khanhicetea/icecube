const componentScripts = import.meta.glob('./**/*.js', { eager: false, base: '../../storage/app/public/icecube' });

const initComponent = async (node) => {
  const name = node.dataset.icecube;
  if (!name) return;
  const mod = await componentScripts[`./${name}.js`]?.();
  if (!mod) return;
  const refs = new Proxy({}, { get: (_, r) => node.querySelector(`[data-ref="${r}"]`) });
  node.dataset.cube = 'icing';
  await mod.default({ root: node, refs, props: JSON.parse(node.dataset.props || '{}') });
  node.dataset.cube = 'iced';
};

(() => {
  document.querySelectorAll('[data-icecube]').forEach(initComponent);

  const observer = new MutationObserver((mutations) => {
    mutations.flatMap(m => [...m.addedNodes]).forEach(node => {
      if (node.nodeType === Node.ELEMENT_NODE && node.dataset.icecube !== undefined) {
        initComponent(node);
        node.querySelectorAll?.('[data-icecube]').forEach(initComponent);
      }
    });
  });

  observer.observe(document.documentElement, { childList: true, subtree: true });
})();
