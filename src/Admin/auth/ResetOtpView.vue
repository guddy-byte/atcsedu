<script setup lang="ts">
import AuthPanel from '../../components/AuthPanel.vue'
</script>

<template>
  <AuthPanel
    eyebrow="Admin authentication"
    title="Admin reset OTP"
    description="This screen is reserved for administrator OTP reset and verification."
    primary-label="Continue to password reset"
    primary-to="/admin/auth/reset-password"
    secondary-label="Back to admin login"
    secondary-to="/admin/auth/login"
  />
</template>