<script setup lang="ts">
import AuthPanel from '../components/AuthPanel.vue'
</script>

<template>
  <AuthPanel
    eyebrow="Student authentication"
    title="Reset password"
    description="Complete the student password recovery flow here before reconnecting this screen to a live authentication service."
    primary-label="Back to login"
    primary-to="/auth/login"
    secondary-label="Create new account"
    secondary-to="/auth/signup"
  />
</template>