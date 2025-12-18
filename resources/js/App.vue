<script setup>
  //import logo from resources/images/storidian-logo.png
  import logo from '../images/storidian-logo.png'
</script>

<template>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card">
          <div class="card-body text-center">
            <img :src="logo" alt="Storidian Logo" class="logo">
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


<style >
.logo {
  height: 60px;
}
</style>