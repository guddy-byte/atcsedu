<script setup lang="ts">
import AuthPanel from '../../components/AuthPanel.vue'
</script>

<template>
  <AuthPanel
    eyebrow="Admin authentication"
    title="Admin login"
    description="Use this screen for administrator sign-in. It is scaffolded and routed so you can replace the placeholder form flow with the real backend later."
    primary-label="Access dashboard"
    primary-to="/admin"
    secondary-label="Reset admin OTP"
    secondary-to="/admin/auth/reset-otp"
  />
</template>