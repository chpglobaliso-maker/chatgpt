const filter = document.getElementById('chp-course-filter');
if (filter) {
  const cards = [...document.querySelectorAll('[data-chp-catalog] [data-level]')];
  const update = () => {
    let count = 0;
    for (const card of cards) { card.hidden = !!filter.value && !card.dataset.level.split(' ').includes(filter.value); if (!card.hidden) count++; }
    document.getElementById('chp-course-count').textContent = `${count} rutas de aprendizaje`;
  };
  filter.addEventListener('change', update); update();
}
