(() => {
  'use strict';

  const MIN_GAIN = 1;
  const MAX_GAIN = 6;
  const STEP = 0.25;
  const STORAGE_KEY = 'kadad_class_audio_gain';

  let gainValue = 1;
  try {
    const saved = Number(localStorage.getItem(STORAGE_KEY));
    if (Number.isFinite(saved)) gainValue = Math.min(MAX_GAIN, Math.max(MIN_GAIN, saved));
  } catch (_) {}

  let audioContext = null;
  const connections = new WeakMap();

  function ensureAudioContext() {
    if (!audioContext) {
      const Ctx = window.AudioContext || window.webkitAudioContext;
      if (!Ctx) return null;
      audioContext = new Ctx();
    }
    if (audioContext.state === 'suspended') audioContext.resume().catch(() => {});
    return audioContext;
  }

  function connectVideo(video) {
    if (!(video instanceof HTMLMediaElement) || video.muted) return;
    if (connections.has(video)) {
      connections.get(video).gain.gain.value = gainValue;
      return;
    }
    const ctx = ensureAudioContext();
    if (!ctx) return;
    try {
      const source = ctx.createMediaElementSource(video);
      const gain = ctx.createGain();
      gain.gain.value = gainValue;
      source.connect(gain);
      gain.connect(ctx.destination);
      connections.set(video, { source, gain });
      video.volume = 1;
    } catch (_) {}
  }

  function updateAll() {
    document.querySelectorAll('video').forEach(video => {
      const item = connections.get(video);
      if (item) item.gain.gain.value = gainValue;
      else if (!video.muted) connectVideo(video);
    });
    updateLabel();
  }

  function setGain(value) {
    gainValue = Math.min(MAX_GAIN, Math.max(MIN_GAIN, Number(value) || 1));
    try { localStorage.setItem(STORAGE_KEY, String(gainValue)); } catch (_) {}
    ensureAudioContext();
    updateAll();
  }

  function percent() { return Math.round(gainValue * 100); }

  function updateLabel() {
    const label = document.getElementById('kadad-audio-gain-label');
    const slider = document.getElementById('kadad-audio-gain-slider');
    const value = document.getElementById('kadad-audio-gain-value');
    if (label) label.textContent = `🔊 ${percent()}%`;
    if (slider) slider.value = String(gainValue);
    if (value) value.textContent = `${percent()}%`;
  }

  function buildUI() {
    if (document.getElementById('kadad-audio-gain')) return;

    const wrap = document.createElement('div');
    wrap.id = 'kadad-audio-gain';
    wrap.innerHTML = `
      <div id="kadad-audio-gain-panel">
        <div class="kadad-audio-gain-title">🔊 تقویت صدای کلاس</div>
        <div class="kadad-audio-gain-value" id="kadad-audio-gain-value">${percent()}%</div>
        <input id="kadad-audio-gain-slider" type="range" min="1" max="6" step="${STEP}" value="${gainValue}" aria-label="تقویت صدای کلاس">
        <div class="kadad-audio-gain-scale"><span>100%</span><span>600%</span></div>
      </div>
      <button id="kadad-audio-gain-toggle" type="button" aria-label="تقویت صدا">🔊 ${percent()}%</button>`;

    const style = document.createElement('style');
    style.textContent = `
      #kadad-audio-gain{position:fixed;left:14px;top:76px;z-index:100005;font-family:Vazirmatn,Tahoma,sans-serif;direction:rtl}
      #kadad-audio-gain-toggle{height:43px;border:1px solid #31425f;background:#101d31;color:#fff;border-radius:12px;padding:0 12px;font:800 12px Vazirmatn;cursor:pointer;box-shadow:0 8px 24px rgba(0,0,0,.25);white-space:nowrap}
      #kadad-audio-gain-panel{display:none;position:absolute;left:0;bottom:51px;width:245px;padding:14px;border:1px solid #31425f;border-radius:15px;background:#0d1729;color:#fff;box-shadow:0 15px 40px rgba(0,0,0,.35)}
      #kadad-audio-gain.open #kadad-audio-gain-panel{display:block}
      .kadad-audio-gain-title{font-weight:900;font-size:13px;margin-bottom:5px}
      .kadad-audio-gain-value{font-size:25px;font-weight:900;text-align:center;margin:8px 0}
      #kadad-audio-gain-slider{width:100%;accent-color:#6d63ff;cursor:pointer;direction:ltr}
      .kadad-audio-gain-scale{display:flex;justify-content:space-between;color:#91a0b8;font-size:10px;margin-top:2px;direction:ltr}
      @media(max-width:900px){
        #kadad-audio-gain{left:12px;top:auto;bottom:194px;z-index:100020}
        #kadad-audio-gain-panel{width:235px;left:0;bottom:51px}
      }
    `;
    document.head.appendChild(style);
    document.body.appendChild(wrap);

    const toggle = document.getElementById('kadad-audio-gain-toggle');
    const slider = document.getElementById('kadad-audio-gain-slider');
    const value = document.getElementById('kadad-audio-gain-value');

    toggle.addEventListener('click', event => {
      event.stopPropagation();
      ensureAudioContext();
      wrap.classList.toggle('open');
      updateAll();
    });

    slider.addEventListener('input', event => {
      setGain(event.target.value);
      value.textContent = `${percent()}%`;
    });

    document.addEventListener('pointerdown', () => ensureAudioContext(), { passive: true });
    document.addEventListener('click', event => {
      if (!wrap.contains(event.target)) wrap.classList.remove('open');
    });

    updateLabel();
  }

  function observe() {
    const observer = new MutationObserver(() => {
      document.querySelectorAll('video').forEach(video => {
        if (!video.muted && video.readyState >= 1) connectVideo(video);
      });
    });
    observer.observe(document.documentElement, { childList: true, subtree: true });
    setInterval(updateAll, 1500);
  }

  function start() {
    buildUI();
    observe();
    updateAll();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
  else start();
})();
