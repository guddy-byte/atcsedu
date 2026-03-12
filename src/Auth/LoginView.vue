<script setup lang="ts">
import AuthPanel from '../components/AuthPanel.vue'
</script>

<template>
  <AuthPanel
    eyebrow="Student authentication"
    title="Student login"
    description="Students returning for exam preparation, materials, or application progress can sign in from this screen."
    primary-label="Continue"
    primary-to="/exam-training"
    secondary-label="Forgot password or OTP?"
    secondary-to="/auth/reset-otp"
    mode="login"
  />
</template>