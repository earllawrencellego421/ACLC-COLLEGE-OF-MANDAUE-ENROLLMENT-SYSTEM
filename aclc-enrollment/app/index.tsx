import React from 'react';
import {
  View, Text, StyleSheet, ScrollView,
  TouchableOpacity, StatusBar, Image,
} from 'react-native';
import { useRouter } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { FontAwesome } from '@expo/vector-icons';

export default function LandingPage() {
  const router = useRouter();

  const courses = [
    {
      id: 'BSIT',
      title: 'BSIT',
      name: 'Bachelor of Science in Information Technology',
      desc: 'Modern IT solutions & software development',
      badge: 'Technology',
      color: '#4F46E5',
      icon: 'laptop',
    },
    {
      id: 'BSCS',
      title: 'BSCS',
      name: 'Bachelor of Science in Computer Science',
      desc: 'Core computing & algorithm expertise',
      badge: 'Computing',
      color: '#10B981',
      icon: 'code',
    },
    {
      id: 'BSHM',
      title: 'BSHM',
      name: 'Bachelor of Science in Hotel Management',
      desc: 'Hospitality industry leadership',
      badge: 'Service',
      color: '#F59E0B',
      icon: 'coffee',
    },
    {
      id: 'BSBA',
      title: 'BSBA',
      name: 'Bachelor of Science in Business Administration',
      desc: 'Business acumen & management skills',
      badge: 'Business',
      color: '#8B5CF6',
      icon: 'briefcase',
    },
  ];

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" />

      <LinearGradient colors={['#1a1a2e', '#16213e', '#0f3460']} style={styles.background} />

      {/* Student Login Button */}
      <TouchableOpacity style={styles.loginBtn} onPress={() => router.push('/login')}>
        <FontAwesome name="sign-in" size={14} color="white" />
        <Text style={styles.loginBtnText}>Student Login</Text>
      </TouchableOpacity>

      <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>

        {/* Header */}
        <View style={styles.header}>
          <View style={styles.logoCircle}>
            <Image
              source={require('../assets/images/logo1.png')}
              style={styles.logoImage}
              resizeMode="contain"
            />
          </View>
          <Text style={styles.headerTitle}>ACLC College</Text>
          <Text style={styles.headerSub}>Student Enrollment Portal</Text>
        </View>

        {/* Hero */}
        <View style={styles.hero}>
          <Text style={styles.heroTitle}>Our Academic Programs</Text>
          <Text style={styles.heroSubtitle}>
            Explore our comprehensive bachelor's degree courses designed to prepare you for career success.
          </Text>
        </View>

        {/* Steps Banner */}
        <View style={styles.stepsCard}>
          <Text style={styles.stepsTitle}>How to Enroll</Text>
          {[
            { step: '1', text: 'Register your account below' },
            { step: '2', text: 'Wait for Admin approval' },
            { step: '3', text: 'Upload your documents once accepted' },
          ].map((item) => (
            <View key={item.step} style={styles.stepRow}>
              <View style={styles.stepBadge}>
                <Text style={styles.stepNum}>{item.step}</Text>
              </View>
              <Text style={styles.stepText}>{item.text}</Text>
            </View>
          ))}
        </View>

        {/* Courses */}
        <View style={styles.grid}>
          {courses.map((course, index) => (
            <View key={index} style={styles.card}>
              <View style={[styles.iconContainer, { backgroundColor: course.color + '20' }]}>
                <FontAwesome name={course.icon as any} size={24} color={course.color} />
              </View>
              <Text style={styles.cardTitle}>{course.title}</Text>
              <Text style={styles.cardName}>{course.name}</Text>
              <Text style={styles.cardDesc}>{course.desc}</Text>
              <View style={[styles.badge, { backgroundColor: course.color }]}>
                <Text style={styles.badgeText}>{course.badge}</Text>
              </View>
            </View>
          ))}
        </View>

        {/* CTA */}
        <TouchableOpacity style={styles.ctaBtn} onPress={() => router.push('/login')}>
          <LinearGradient colors={['#4F46E5', '#7C3AED']} style={styles.ctaGradient} start={{ x: 0, y: 0 }} end={{ x: 1, y: 0 }}>
            <FontAwesome name="pencil-square-o" size={18} color="white" />
            <Text style={styles.ctaText}>Apply for Enrollment</Text>
          </LinearGradient>
        </TouchableOpacity>

        <View style={{ height: 40 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  background: { position: 'absolute', left: 0, right: 0, top: 0, bottom: 0 },
  scrollContent: { padding: 20, paddingTop: 110 },

  loginBtn: {
    position: 'absolute',
    top: 50,
    right: 20,
    zIndex: 10,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: 'rgba(255,255,255,0.15)',
    paddingVertical: 10,
    paddingHorizontal: 18,
    borderRadius: 30,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.25)',
  },
  loginBtnText: { color: 'white', fontWeight: '700', fontSize: 13 },

  header: { alignItems: 'center', marginBottom: 32 },
  logoCircle: {
    width: 120,
    height: 120,
    borderRadius: 60,
    backgroundColor: 'white',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 14,
    shadowColor: '#4F46E5',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.5,
    shadowRadius: 16,
    elevation: 14,
  },
  logoImage: { width: 90, height: 90 },
  headerTitle: { fontSize: 30, fontWeight: '900', color: 'white', letterSpacing: 1 },
  headerSub: { fontSize: 13, color: 'rgba(255,255,255,0.6)', marginTop: 4 },

  hero: { marginBottom: 28, alignItems: 'center' },
  heroTitle: { fontSize: 24, fontWeight: '800', color: 'white', textAlign: 'center', marginBottom: 10 },
  heroSubtitle: { fontSize: 14, color: 'rgba(255,255,255,0.7)', textAlign: 'center', lineHeight: 22 },

  stepsCard: {
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderRadius: 20,
    padding: 20,
    marginBottom: 28,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.12)',
  },
  stepsTitle: { color: 'white', fontWeight: '800', fontSize: 16, marginBottom: 16 },
  stepRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 12 },
  stepBadge: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: '#4F46E5',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 14,
  },
  stepNum: { color: 'white', fontWeight: '900', fontSize: 14 },
  stepText: { color: 'rgba(255,255,255,0.85)', fontSize: 14, flex: 1 },

  grid: { gap: 16, marginBottom: 24 },
  card: {
    backgroundColor: 'rgba(255,255,255,0.95)',
    borderRadius: 18,
    padding: 22,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.2,
    shadowRadius: 10,
    elevation: 8,
  },
  iconContainer: {
    width: 56,
    height: 56,
    borderRadius: 28,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 14,
  },
  cardTitle: { fontSize: 22, fontWeight: '900', color: '#1a1a2e', marginBottom: 6 },
  cardName: { fontSize: 14, fontWeight: '600', color: '#4B5563', textAlign: 'center', marginBottom: 6 },
  cardDesc: { fontSize: 13, color: '#9CA3AF', textAlign: 'center', marginBottom: 14, lineHeight: 18 },
  badge: { paddingVertical: 5, paddingHorizontal: 14, borderRadius: 20 },
  badgeText: { color: 'white', fontWeight: '700', fontSize: 11, textTransform: 'uppercase', letterSpacing: 0.5 },

  ctaBtn: { borderRadius: 16, overflow: 'hidden', marginTop: 4 },
  ctaGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
    paddingVertical: 18,
    paddingHorizontal: 30,
  },
  ctaText: { color: 'white', fontWeight: '800', fontSize: 16 },
});