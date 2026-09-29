(() => {
  const section = document.getElementById('divider');
  if (!section) return;
  const track = section.querySelector('.divider__track');

  section.classList.add('is-pre');
  const io = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
      if (track) track.classList.toggle('is-paused', !e.isIntersecting);
      if (e.isIntersecting) section.classList.remove('is-pre');
    });
  }, { threshold: 0.25 });
  io.observe(section);
})();
