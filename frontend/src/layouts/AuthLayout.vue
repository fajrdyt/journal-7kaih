<template>
  <main class="auth-page">
    <section class="auth-shell">
      <div class="auth-form-panel">
        <div class="desktop-brand">
          <div class="brand-mark">
            7K
          </div>

          <span class="brand-name">
            Jurnal 7 KAIH
          </span>
        </div>

        <div class="auth-form-content">
          <slot />
        </div>
      </div>

      <aside class="auth-hero-panel">
        <div class="hero-decoration hero-decoration-top" />
        <div class="hero-decoration hero-decoration-bottom" />

        <div class="mobile-brand">
          <div class="brand-mark brand-mark-light">
            J7
          </div>

          <span class="brand-name brand-name-light">
            Jurnal 7 KAIH
          </span>
        </div>

        <div class="auth-hero-content">
          <div class="auth-hero-copy">
            <slot name="hero" />
          </div>

          <div class="auth-illustration">
            <slot name="illustration" />
          </div>
        </div>
      </aside>
    </section>
  </main>
</template>

<style scoped>
.auth-page {
  position: fixed;
  inset: 0;
  width: 100%;
  height: 100vh;
  height: 100svh;
  display: grid;
  place-items: center;
  overflow: hidden;
  box-sizing: border-box;
  padding: 20px;
  background:
    radial-gradient(
      circle at top left,
      rgba(14, 165, 233, 0.11),
      transparent 34%
    ),
    #f4f7fa;
}

.auth-shell {
  width: min(1180px, calc(100vw - 40px));
  height: min(680px, calc(100svh - 40px));
  min-height: 560px;
  display: grid;
  grid-template-columns:
    minmax(390px, 0.94fr)
    minmax(520px, 1.06fr);
  overflow: hidden;
  border: 1px solid #dbe2ea;
  border-radius: 26px;
  background: #ffffff;
  box-shadow: 0 24px 65px rgba(15, 23, 42, 0.12);
}

.auth-form-panel {
  position: relative;
  z-index: 2;
  min-width: 0;
  min-height: 0;
  display: flex;
  flex-direction: column;
  box-sizing: border-box;
  padding: 38px 52px;
  background: #ffffff;
}

.desktop-brand,
.mobile-brand {
  display: flex;
  align-items: center;
  gap: 11px;
}

.brand-mark {
  width: 38px;
  height: 38px;
  display: grid;
  flex-shrink: 0;
  place-items: center;
  border-radius: 11px;
  background: #168ce0;
  color: #ffffff;
  font-size: 14px;
  font-weight: 800;
  letter-spacing: -0.03em;
}

.brand-name {
  color: #172033;
  font-size: 16px;
  font-weight: 800;
}

.auth-form-content {
  width: 100%;
  max-width: 400px;
  margin: auto;
}

.auth-hero-panel {
  position: relative;
  min-width: 0;
  min-height: 0;
  overflow: hidden;
  box-sizing: border-box;
  padding: 38px 52px 0;
  color: #ffffff;
  background:
    linear-gradient(
      145deg,
      #1179ce 0%,
      #168fe4 52%,
      #2196e8 100%
    );
}

.auth-hero-content {
  position: relative;
  z-index: 2;
  width: 100%;
  height: 100%;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.auth-hero-copy {
  position: relative;
  z-index: 3;
  width: 100%;
  max-width: 520px;
  flex-shrink: 0;
}

.auth-illustration {
  position: relative;
  z-index: 2;
  min-height: 0;
  display: flex;
  flex: 1;
  align-items: flex-end;
  justify-content: flex-end;
  margin-top: 8px;
  margin-right: -22px;
}

.auth-illustration :deep(img) {
  width: min(78%, 445px);
  max-width: 445px;
  max-height: 310px;
  display: block;
  object-fit: contain;
  object-position: right bottom;
  filter: drop-shadow(
    0 16px 26px rgba(3, 105, 161, 0.14)
  );
}

.mobile-brand {
  display: none;
}

.hero-decoration {
  position: absolute;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.08);
  pointer-events: none;
}

.hero-decoration-top {
  width: 250px;
  height: 250px;
  top: -105px;
  left: -105px;
}

.hero-decoration-bottom {
  width: 350px;
  height: 350px;
  right: -135px;
  bottom: -165px;
}

@media (min-width: 901px) and (max-width: 1200px) {
  .auth-shell {
    width: calc(100vw - 32px);
  }

  .auth-form-panel {
    padding-right: 42px;
    padding-left: 42px;
  }

  .auth-hero-panel {
    padding-right: 40px;
    padding-left: 40px;
  }

  .auth-illustration {
    margin-right: -18px;
  }

  .auth-illustration :deep(img) {
    width: min(76%, 400px);
    max-width: 400px;
    max-height: 285px;
  }
}

@media (min-width: 901px) and (max-height: 700px) {
  .auth-page {
    padding: 14px;
  }

  .auth-shell {
    width: min(1120px, calc(100vw - 28px));
    height: calc(100svh - 28px);
    min-height: 0;
  }

  .auth-form-panel {
    padding-top: 28px;
    padding-bottom: 28px;
  }

  .auth-hero-panel {
    padding-top: 28px;
  }

  .auth-illustration {
    margin-top: 4px;
    margin-right: -16px;
  }

  .auth-illustration :deep(img) {
    width: min(70%, 355px);
    max-width: 355px;
    max-height: 225px;
  }
}

@media (max-width: 900px) {
  .auth-page {
    position: static;
    width: 100%;
    height: auto;
    min-height: 100vh;
    min-height: 100svh;
    display: block;
    overflow: visible;
    padding: 0;
    background: #ffffff;
  }

  .auth-shell {
    width: 100%;
    height: auto;
    min-height: 100vh;
    min-height: 100svh;
    display: flex;
    flex-direction: column;
    overflow: visible;
    border: 0;
    border-radius: 0;
    box-shadow: none;
  }

  .auth-hero-panel {
    order: 1;
    min-height: 220px;
    padding: 26px 24px 54px;
  }

  .auth-form-panel {
    order: 2;
    min-height: calc(100svh - 196px);
    margin-top: -24px;
    padding: 28px 24px 40px;
    border-radius: 24px 24px 0 0;
    box-shadow:
      0 -10px 35px rgba(15, 23, 42, 0.07);
  }

  .desktop-brand {
    display: none;
  }

  .mobile-brand {
    position: relative;
    z-index: 3;
    display: flex;
  }

  .brand-mark-light {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.22);
    backdrop-filter: blur(8px);
  }

  .brand-name-light {
    color: #ffffff;
  }

  .auth-hero-content {
    height: auto;
    min-height: 0;
  }

  .auth-hero-copy {
    margin-top: 24px;
  }

  .auth-illustration {
    display: none;
  }

  .auth-form-content {
    max-width: 460px;
  }

  .hero-decoration-top {
    width: 210px;
    height: 210px;
    top: -92px;
    right: -65px;
    left: auto;
  }

  .hero-decoration-bottom {
    display: none;
  }
}

@media (max-width: 480px) {
  .auth-hero-panel {
    min-height: 210px;
    padding:
      max(22px, env(safe-area-inset-top))
      20px
      50px;
  }

  .auth-form-panel {
    min-height: calc(100svh - 186px);
    padding: 26px 20px 36px;
  }
}
</style>