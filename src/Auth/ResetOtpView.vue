<script setup lang="ts">
import AuthPanel from '../components/AuthPanel.vue'
</script>

<template>
  <AuthPanel
    eyebrow="Student authentication"
    title="Reset OTP"
    description="Use this scaffold to request and verify the OTP required for student account recovery."
    primary-label="Proceed to reset password"
    primary-to="/auth/reset-password"
    secondary-label="Return to login"
    secondary-to="/auth/login"
  />
</template>