<script setup lang="ts">
import AuthPanel from '../components/AuthPanel.vue'
</script>

<template>
  <AuthPanel
    eyebrow="Student authentication"
    title="Student signup"
    description="Applicants and students can begin their exam journey here. This route is intended to lead into your real onboarding flow later."
    primary-label="Create account"
    primary-to="/auth/account-success"
    secondary-label="Already have an account? Login"
    secondary-to="/auth/login"
    mode="signup"
  />
</template>