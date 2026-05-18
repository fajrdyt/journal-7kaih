<template>
  <div class="login-page">
    <div class="login-container">
      <!-- LEFT SIDE -->
      <div class="left-section">
        <h2>Selamat Datang,</h2>

        <div class="image-placeholder"></div>

        <p>
          Awali Pagimu <br />
          Dengan Semangat!!!
        </p>
      </div>

      <!-- RIGHT SIDE -->
      <div class="right-section">
        <!-- Logo -->
        <div class="logo-wrapper">
          <div class="diamond"></div>

          <h1>
            SMA N 1 MIRIT <br />
            KEBUMEN
          </h1>
        </div>

        <!-- Form -->
        <div class="form-card">
          <input type="text" placeholder="NISN" />

          <input type="password" placeholder="Password" />

          <button>Masuk</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Vue 3 Composition API
</script>

<style scoped>
.login-page {
  width: 100%;
  min-height: 100vh;
  background-color: #ececec;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
  box-sizing: border-box;
  font-family: "Poppins", sans-serif;
}

.login-container {
  width: 100%;
  max-width: 1200px;
  min-height: 650px;
  background: #f5f5f5;
  display: flex;
  border-radius: 6px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

/* =========================
   LEFT
========================= */
.left-section {
  width: 45%;
  background: #d9d9d9;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 35px;
  color: white;
  padding: 30px;
  box-sizing: border-box;
}

.left-section h2 {
  font-size: 32px;
  font-weight: 600;
  margin: 0;
}

.image-placeholder {
  width: 240px;
  height: 320px;
  border: 2px solid #a7a7a7;
  position: relative;
  background: #efefef;
}

.image-placeholder::before,
.image-placeholder::after {
  content: "";
  position: absolute;
  width: 2px;
  height: 100%;
  background: #a7a7a7;
  top: 0;
  left: 50%;
  transform-origin: center;
}

.image-placeholder::before {
  transform: rotate(36deg);
}

.image-placeholder::after {
  transform: rotate(-36deg);
}

.left-section p {
  text-align: center;
  font-size: 30px;
  font-weight: 500;
  line-height: 1.4;
  margin: 0;
}

/* =========================
   RIGHT
========================= */
.right-section {
  width: 55%;
  background: #f7f7f7;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: center;
  padding: 60px 40px 40px;
  box-sizing: border-box;
}

.logo-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 25px;
}

.diamond {
  width: 110px;
  height: 110px;
  border: 2px solid #b0b0b0;
  transform: rotate(45deg);
}

.logo-wrapper h1 {
  text-align: center;
  color: #9a9a9a;
  font-size: 30px;
  line-height: 1.2;
  letter-spacing: 2px;
  font-weight: 600;
  margin: 0;
}

/* =========================
   FORM
========================= */
.form-card {
  width: 100%;
  max-width: 520px;
  background: #d9d9d9;
  border-radius: 18px;
  padding: 35px;
  display: flex;
  flex-direction: column;
  gap: 22px;
  box-sizing: border-box;
}

.form-card input {
  width: 100%;
  height: 52px;
  border-radius: 10px;
  border: 1px solid #bdbdbd;
  padding: 0 18px;
  font-size: 18px;
  outline: none;
  box-sizing: border-box;
}

.form-card input::placeholder {
  color: #9a9a9a;
}

.form-card button {
  width: 150px;
  height: 50px;
  margin: 20px auto 0;
  border: 1px solid #bdbdbd;
  border-radius: 10px;
  background: #f3f3f3;
  color: #8d8d8d;
  font-size: 20px;
  cursor: pointer;
  transition: 0.3s;
}

.form-card button:hover {
  background: #e5e5e5;
}

/* =========================
   RESPONSIVE
========================= */
@media (max-width: 900px) {
  .login-container {
    flex-direction: column;
  }

  .left-section,
  .right-section {
    width: 100%;
  }

  .left-section {
    padding: 50px 20px;
  }

  .right-section {
    gap: 50px;
  }

  .left-section h2 {
    font-size: 26px;
  }

  .left-section p {
    font-size: 24px;
  }

  .logo-wrapper h1 {
    font-size: 24px;
  }
}
</style>