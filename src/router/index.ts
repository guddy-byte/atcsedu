import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'

import AccountSuccessfullyView from '../Auth/AccountSuccessfullyView.vue'
import LoginView from '../Auth/LoginView.vue'
import ResetOtpView from '../Auth/ResetOtpView.vue'
import ResetPasswordView from '../Auth/ResetPasswordView.vue'
import SignupView from '../Auth/SignupView.vue'
import AdminDashboardView from '../Admin/AdminDashboardView.vue'
import AdminLoginView from '../Admin/auth/AdminLoginView.vue'
import AdminResetOtpView from '../Admin/auth/ResetOtpView.vue'
import AdminResetPasswordView from '../Admin/auth/ResetPasswordView.vue'
import CartView from '../views/CartView.vue'
import ContactView from '../views/ContactView.vue'
import ContentPageView from '../views/ContentPageView.vue'
import ExamTrainingView from '../views/ExamTrainingView.vue'
import HomeView from '../views/HomeView.vue'
import MaterialsView from '../views/MaterialsView.vue'
import { isStudentAuthenticated, resolveProtectedStudentRoute } from '../utils/studentAuth'

const routes: RouteRecordRaw[] = [
  { path: '/', name: 'home', component: HomeView },
  {
    path: '/about',
    name: 'about',
    component: ContentPageView,
    props: {
      eyebrow: 'About ATCS Edu',
      title: 'A simple education marketplace built for students and exam applicants.',
      description:
        'ATCS Edu packages learning materials, free downloads, and premium exam preparation resources into one clean digital platform.',
      accent: 'Institution-aligned resources, clearer student journeys, and simple access to exam readiness support.',
    },
  },
  {
    path: '/materials/free',
    name: 'free-materials',
    component: MaterialsView,
    props: {
      mode: 'free',
      title: 'Free materials',
      description: 'Browse free educational downloads, starter packs, and support resources.',
    },
  },
  {
    path: '/materials/paid',
    name: 'paid-materials',
    component: MaterialsView,
    props: {
      mode: 'paid',
      title: 'Paid materials',
      description: 'Explore premium revision products, practice bundles, and guided study packs.',
    },
  },
  {
    path: '/exam-training',
    name: 'exam-training',
    component: ExamTrainingView,
  },
  {
    path: '/contact',
    name: 'contact',
    component: ContactView,
  },
  { path: '/cart', name: 'cart', component: CartView },
  { path: '/admin', name: 'admin', component: AdminDashboardView },
  { path: '/admin/auth/login', name: 'admin-login', component: AdminLoginView },
  { path: '/admin/auth/reset-otp', name: 'admin-reset-otp', component: AdminResetOtpView },
  {
    path: '/admin/auth/reset-password',
    name: 'admin-reset-password',
    component: AdminResetPasswordView,
  },
  { path: '/auth/signup', name: 'student-signup', component: SignupView },
  { path: '/auth/login', name: 'student-login', component: LoginView },
  { path: '/auth/reset-otp', name: 'student-reset-otp', component: ResetOtpView },
  {
    path: '/auth/reset-password',
    name: 'student-reset-password',
    component: ResetPasswordView,
  },
  {
    path: '/auth/account-success',
    name: 'student-account-success',
    component: AccountSuccessfullyView,
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  if (to.name !== 'exam-training' || isStudentAuthenticated()) {
    return true
  }

  return resolveProtectedStudentRoute('/exam-training')
})

export default router