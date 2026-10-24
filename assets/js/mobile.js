/* KAGOFANSUB mobile helpers */
document.addEventListener("DOMContentLoaded", () => {
  const mobileViewport = window.matchMedia("(max-width: 760px)");

  document.querySelectorAll(".topbar").forEach(header => {
    if (!header.querySelector(".mobile-random-button")) {
      const randomButton = document.createElement("button");
      randomButton.className = "mobile-random-button";
      randomButton.type = "button";
      randomButton.setAttribute("aria-label", "Open a random anime");
      randomButton.innerHTML = '<span aria-hidden="true"></span>';
      randomButton.addEventListener("click", () => {
        const base = (window.kagoData && window.kagoData.homeUrl) || "/";
        window.location.href = base + (base.indexOf("?") === -1 ? "?" : "&") + "kago_random=1";
      });
      header.appendChild(randomButton);
    }

    const updateHeaderScrollState = () => {
      header.classList.toggle("is-scrolled", window.scrollY > 2);
    };
    window.addEventListener("scroll", updateHeaderScrollState, { passive: true });
    updateHeaderScrollState();
  });

  const synopsis = document.querySelector("#detail-synopsis");
  const synopsisToggle = document.querySelector(".synopsis-toggle");
  if (synopsis && synopsisToggle) {
    synopsisToggle.addEventListener("click", () => {
      const expanded = synopsis.classList.toggle("is-expanded");
      synopsisToggle.textContent = expanded ? "See less" : "See more";
      synopsisToggle.setAttribute("aria-expanded", String(expanded));
    });
  }

  document.querySelectorAll("#latest .row, #season.row").forEach(carouselRow => {
    const carouselGuide = carouselRow.nextElementSibling;
    const carouselTrack = carouselGuide?.querySelector(".mobile-carousel-track");
    const carouselProgress = carouselGuide?.querySelector(".mobile-carousel-progress");
    if (!carouselGuide?.classList.contains("mobile-carousel-guide") || !carouselTrack || !carouselProgress) return;

    const updateCarouselGuide = () => {
      const maxScroll = carouselRow.scrollWidth - carouselRow.clientWidth;
      const hasOverflow = maxScroll > 2;
      carouselGuide.hidden = !mobileViewport.matches || !hasOverflow;
      if (!hasOverflow) return;

      const visiblePercent = Math.min(100, carouselRow.clientWidth / carouselRow.scrollWidth * 100);
      const scrollPercent = carouselRow.scrollLeft / maxScroll;
      carouselProgress.style.width = `${visiblePercent}%`;
      carouselProgress.style.left = `${scrollPercent * (100 - visiblePercent)}%`;
      carouselTrack.setAttribute("aria-valuenow", String(Math.round(scrollPercent * 100)));
    };

    carouselRow.addEventListener("scroll", updateCarouselGuide, { passive: true });
    window.addEventListener("resize", updateCarouselGuide);
    mobileViewport.addEventListener("change", updateCarouselGuide);
    updateCarouselGuide();
  });

  // Bind only menu buttons that do not already have page-specific handlers.
  document.querySelectorAll("[data-mobile-menu]").forEach(button => {
    const nav = document.querySelector(".topbar .nav");
    if (!nav) return;
    button.addEventListener("click", () => {
      const opened = nav.classList.toggle("open");
      button.setAttribute("aria-expanded", String(opened));
    });
  });

  // Move the existing search control into the bottom navigation on mobile.
  const search = document.querySelector(".topbar .search");
  const mobileNav = document.querySelector(".mobile-bottom-nav");
  if (search && mobileNav && !search.dataset.mobileBound) {
    search.dataset.mobileBound = "true";
    const input = search.querySelector("input");
    const icon = search.querySelector("span");
    if (input && icon) {
      const searchIcon = icon.textContent.trim() || "⌕";
      const originalParent = search.parentNode;
      const originalNextSibling = search.nextSibling;
      const placeholder = document.createComment("mobile search position");
      originalParent.insertBefore(placeholder, search);
      const mobileMedia = window.matchMedia("(max-width: 760px)");
      let closeAnimationTimer;
      let closeButton = null;

      icon.style.cursor = "pointer";
      icon.setAttribute("role", "button");
      icon.setAttribute("tabindex", "0");
      icon.setAttribute("aria-label", "Open search");
      icon.setAttribute("aria-expanded", "false");

      const setSearchLocation = () => {
        if (mobileMedia.matches) {
          search.classList.add("mobile-search-control");
          mobileNav.appendChild(search);
          return;
        }

        closeButton?.remove();
        closeButton = null;
        search.classList.remove("search-open", "search-closing", "mobile-search-control");
        icon.textContent = searchIcon;
        icon.setAttribute("aria-label", "Open search");
        icon.setAttribute("aria-expanded", "false");
        if (search.parentNode !== originalParent) {
          originalParent.insertBefore(search, placeholder.nextSibling || originalNextSibling);
        }
      };

      const createCloseButton = () => {
        if (closeButton) return;
        closeButton = document.createElement("button");
        closeButton.type = "button";
        closeButton.className = "mobile-search-close";
        closeButton.setAttribute("aria-label", "Close search");
        closeButton.innerHTML = '<span aria-hidden="true"></span><span>Close</span>';
        closeButton.addEventListener("click", closeSearch);
        mobileNav.appendChild(closeButton);
      };

      const closeSearch = () => {
        if (!search.classList.contains("search-open")) return;
        clearTimeout(closeAnimationTimer);
        search.classList.remove("search-open");
        search.classList.add("search-closing");
        closeButton?.classList.add("is-closing");
        input.blur();
        closeAnimationTimer = setTimeout(() => {
          search.classList.remove("search-closing");
          closeButton?.remove();
          closeButton = null;
          if (mobileMedia.matches) {
            search.classList.add("search-returning");
            mobileNav.appendChild(search);
            setTimeout(() => search.classList.remove("search-returning"), 240);
          }
          icon.textContent = searchIcon;
          icon.setAttribute("aria-label", "Open search");
          icon.setAttribute("aria-expanded", "false");
        }, 240);
      };

      const toggleSearch = () => {
        if (!mobileMedia.matches) return;
        const opened = !search.classList.contains("search-open");
        clearTimeout(closeAnimationTimer);
        if (opened) {
          search.classList.remove("search-closing");
          search.classList.add("search-open");
          originalParent.insertBefore(search, placeholder.nextSibling || originalNextSibling);
          createCloseButton();
          icon.textContent = searchIcon;
          icon.setAttribute("aria-label", "Search");
          icon.setAttribute("aria-expanded", "true");
          input.focus({ preventScroll: true });
        } else {
          closeSearch();
        }
      };

      icon.addEventListener("click", toggleSearch);
      icon.addEventListener("keydown", event => {
        if (event.key === "Enter" || event.key === " ") {
          event.preventDefault();
          toggleSearch();
        }
      });
      input.addEventListener("keydown", event => {
        if (event.key === "Escape" && search.classList.contains("search-open")) {
          event.preventDefault();
          closeSearch();
          closeButton?.focus();
        }
      });

      setSearchLocation();
      mobileMedia.addEventListener("change", setSearchLocation);
    }
  }
});
