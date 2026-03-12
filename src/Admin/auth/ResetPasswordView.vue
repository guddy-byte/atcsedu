<script setup lang="ts">
import AuthPanel from '../../components/AuthPanel.vue'
</script>

<template>
  <AuthPanel
    eyebrow="Admin authentication"
    title="Admin reset password"
    description="Finish the admin recovery flow here. Replace this scaffold with your production password reset logic when ready."
    primary-label="Return to admin login"
    primary-to="/admin/auth/login"
    secondary-label="Open dashboard"
    secondary-to="/admin"
  />
</template>