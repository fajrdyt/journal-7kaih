<template>
  <main class="simple-page">
    <section class="simple-card">
      <div class="code">404</div>
      <h1>Halaman tidak ditemukan</h1>
      <p>Alamat yang kamu buka tidak tersedia atau sudah dipindahkan.</p>
      <router-link class="btn" to="/login">Kembali</router-link>
    </section>
  </main>
</template>

<style scoped>
.simple-page{min-height:100vh;background:#f5f8fc;display:grid;place-items:center;padding:24px;color:#18213a}.simple-card{max-width:460px;background:white;border:1px solid #e4edf6;border-radius:28px;padding:36px;text-align:center;box-shadow:0 22px 50px rgba(24,33,58,.08)}.code{width:74px;height:74px;border-radius:24px;background:#fff5e3;color:#c06b00;display:grid;place-items:center;margin:0 auto 20px;font-size:24px;font-weight:900}h1{margin:0 0 10px}p{margin:0 0 24px;color:#667085}.btn{display:inline-flex;text-decoration:none;background:#3ab7f5;color:white;font-weight:800;border-radius:14px;padding:12px 18px}
</style>
