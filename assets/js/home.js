/* Home page: Latest Updates arrows and hero slider (from the static index.html). */
    (function () {
      document.querySelectorAll(".pills em").forEach((pill) => {
        pill.addEventListener("click", (event) => {
          event.preventDefault();
          event.stopPropagation();
        });
      });

      const latestRow = document.querySelector("#latest .row");
      const previousButton = document.getElementById("prev");
      const nextButton = document.getElementById("next");
      const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
      let slideFrame = 0;

      if (!latestRow || !previousButton || !nextButton) return;

      function slideLatest(direction) {
        const card = latestRow.querySelector(".card");
        if (!card) return;

        const gap = parseFloat(getComputedStyle(latestRow).columnGap) || 0;
        const distance = card.getBoundingClientRect().width + gap;
        const maxScroll = latestRow.scrollWidth - latestRow.clientWidth;
        const atStart = latestRow.scrollLeft <= 1;
        const atEnd = latestRow.scrollLeft >= maxScroll - 1;

        if (slideFrame) cancelAnimationFrame(slideFrame);

        if ((direction < 0 && atStart) || (direction > 0 && atEnd)) {
          latestRow.scrollLeft = direction < 0 ? maxScroll : 0;
          return;
        }

        const start = latestRow.scrollLeft;
        const target = Math.max(0, Math.min(maxScroll, start + distance * direction));
        if (reduceMotion.matches) {
          latestRow.scrollLeft = target;
          return;
        }

        const duration = 220;
        const startedAt = performance.now();
        function animateSlide(now) {
          const progress = Math.min((now - startedAt) / duration, 1);
          const eased = 1 - Math.pow(1 - progress, 3);
          latestRow.scrollLeft = start + (target - start) * eased;
          if (progress < 1) slideFrame = requestAnimationFrame(animateSlide);
          else slideFrame = 0;
        }
        slideFrame = requestAnimationFrame(animateSlide);
      }

      previousButton.addEventListener("click", () => slideLatest(-1));
      nextButton.addEventListener("click", () => slideLatest(1));
    })();
  
    (function () {
      const slides = document.querySelectorAll("#hero .slide");
      if (!slides.length) return;
      const dotsBox = document.createElement("div");
      dotsBox.className = "dots";
      dotsBox.id = "dots";
      let hi = 0, timer;
      slides.forEach((_, i) => {
        const b = document.createElement("button");
        b.setAttribute("aria-label", "Slide " + (i + 1));
        if (i === 0) b.classList.add("on");
        b.onclick = () => go(i);
        dotsBox.appendChild(b);
      });
      const dots = dotsBox.querySelectorAll("button");
      function go(i) {
        hi = (i + slides.length) % slides.length;
        slides.forEach((s, j) => {
          s.classList.toggle("on", j === hi);
          if (j === hi) s.appendChild(dotsBox);
        });
        dots.forEach((d, j) => d.classList.toggle("on", j === hi));
        clearInterval(timer);
        timer = setInterval(() => go(hi + 1), 6000);
      }
      const hero = document.getElementById("hero");
      let touchStartX = 0;
      let touchStartY = 0;
      let suppressClick = false;
      let suppressClickTimer;

      hero.addEventListener("touchstart", (event) => {
        const touch = event.changedTouches[0];
        touchStartX = touch.clientX;
        touchStartY = touch.clientY;
        clearInterval(timer);
      }, { passive: true });

      hero.addEventListener("touchend", (event) => {
        const touch = event.changedTouches[0];
        const deltaX = touch.clientX - touchStartX;
        const deltaY = touch.clientY - touchStartY;

        if (Math.abs(deltaX) > 45 && Math.abs(deltaX) > Math.abs(deltaY) * 1.2) {
          suppressClick = true;
          clearTimeout(suppressClickTimer);
          suppressClickTimer = setTimeout(() => { suppressClick = false; }, 500);
          go(hi + (deltaX < 0 ? 1 : -1));
        } else {
          timer = setInterval(() => go(hi + 1), 6000);
        }
      }, { passive: true });

      hero.addEventListener("touchcancel", () => {
        timer = setInterval(() => go(hi + 1), 6000);
      }, { passive: true });

      hero.addEventListener("click", (event) => {
        if (!suppressClick) return;
        event.preventDefault();
        event.stopPropagation();
        suppressClick = false;
        clearTimeout(suppressClickTimer);
      }, true);

      hero.addEventListener("mouseenter", () => clearInterval(timer));
      hero.addEventListener("mouseleave", () => timer = setInterval(() => go(hi + 1), 6000));
      go(0);
    })();
  
