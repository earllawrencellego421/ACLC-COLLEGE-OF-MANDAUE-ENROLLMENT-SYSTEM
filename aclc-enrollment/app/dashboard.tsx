import React, { useState, useEffect } from 'react';
import {
  View, Text, StyleSheet, ScrollView, TouchableOpacity,
  Alert, ActivityIndicator,
} from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { supabase } from '../lib/supabase';
import { FontAwesome } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import * as ImagePicker from 'expo-image-picker';
import * as ImageManipulator from 'expo-image-manipulator';
import { decode } from 'base64-arraybuffer';

// ---------- Document config ----------
const REQUIRED_DOCS = [
  { key: 'report_card',    label: 'Report Card',                 icon: 'file-text-o' },
  { key: 'tor_dismissal',  label: 'TOR & Honorable Dismissal',   icon: 'graduation-cap' },
  { key: 'good_moral',     label: 'Good Moral Certificate',      icon: 'certificate' },
  { key: 'psa_birth_cert', label: 'Photocopy of PSA Live Birth', icon: 'id-card-o' },
];

// ---------- Types ----------
type DocStatus = 'not_uploaded' | 'uploading' | 'uploaded';
type DocStatuses = Record<string, DocStatus>;

// ---------- Enrollment status helpers ----------
// Possible values in DB: 'PENDING' | 'ACCEPTED' | 'REJECTED' | 'ENROLLED' | 'NOT ENROLLED'
// 'ACCEPTED'     = admin approved, but docs not yet complete
// 'ENROLLED'     = all 4 docs submitted
// 'NOT ENROLLED' = was enrolled but docs became incomplete (edge-case re-upload)
const isAcceptedOrEnrolled = (status: string) =>
  status === 'ACCEPTED' || status === 'ENROLLED' || status === 'NOT ENROLLED';

export default function StudentDashboard() {
  const { student_id } = useLocalSearchParams();
  const router = useRouter();

  const [student, setStudent] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [docStatuses, setDocStatuses] = useState<DocStatuses>({});
  const [enrollmentLoading, setEnrollmentLoading] = useState(false);

  useEffect(() => {
    fetchStudentData();
  }, []);

  const fetchStudentData = async () => {
    setLoading(true);
    const { data, error } = await supabase
      .from('students')
      .select('*')
      .eq('student_id', student_id)
      .single();

    if (data) {
      setStudent(data);
      const initial: DocStatuses = {};
      REQUIRED_DOCS.forEach((doc) => {
        initial[doc.key] = data[doc.key] ? 'uploaded' : 'not_uploaded';
      });
      setDocStatuses(initial);
    }

    if (error) Alert.alert('Error', 'Could not fetch student data.');
    setLoading(false);
  };

  // ---------- Auto-update enrollment after every upload ----------
  const syncEnrollmentStatus = async (newStatuses: DocStatuses) => {
    const allUploaded = REQUIRED_DOCS.every((d) => newStatuses[d.key] === 'uploaded');
    const currentEnrollment = student?.is_accepted ?? '';

    // Only update if status needs to change
    const shouldBeEnrolled    = allUploaded && currentEnrollment !== 'ENROLLED';
    const shouldBeNotEnrolled = !allUploaded && currentEnrollment === 'ENROLLED';

    if (!shouldBeEnrolled && !shouldBeNotEnrolled) return;

    const newStatus = allUploaded ? 'ENROLLED' : 'NOT ENROLLED';
    setEnrollmentLoading(true);

    const { error } = await supabase
      .from('students')
      .update({ is_accepted: newStatus })
      .eq('student_id', student_id);

    if (!error) {
      setStudent((prev: any) => ({ ...prev, is_accepted: newStatus }));
      if (allUploaded) {
        Alert.alert(
          '🎉 Congratulations!',
          'All required documents have been submitted. You are now officially ENROLLED!',
          [{ text: 'Great!', style: 'default' }]
        );
      }
    }

    setEnrollmentLoading(false);
  };

  const handleUpload = async (docKey: string, docLabel: string) => {
    const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (status !== 'granted') {
      Alert.alert('Permission Needed', 'Please allow access to your photos to upload documents.');
      return;
    }

    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsEditing: false,
      quality: 0.8,
      exif: false,
    });

    if (result.canceled) return;

    const asset = result.assets[0];
    const fileName = `${student_id}_${docKey}_${Date.now()}.jpg`;
    const filePath = `documents/${fileName}`;
    const contentType = 'image/jpeg';

    setDocStatuses((prev) => ({ ...prev, [docKey]: 'uploading' }));

    try {
      const manipulated = await ImageManipulator.manipulateAsync(
        asset.uri,
        [],
        { compress: 0.8, format: ImageManipulator.SaveFormat.JPEG, base64: true }
      );

      const base64 = manipulated.base64;
      if (!base64) throw new Error('Failed to convert image. Please try again.');

      const arrayBuffer = decode(base64);

      const { error: uploadError } = await supabase.storage
        .from('student-documents')
        .upload(filePath, arrayBuffer, { contentType, upsert: true });

      if (uploadError) throw uploadError;

      const { data: urlData } = supabase.storage
        .from('student-documents')
        .getPublicUrl(filePath);

      const { error: dbError } = await supabase
        .from('students')
        .update({ [docKey]: urlData.publicUrl })
        .eq('student_id', student_id);

      if (dbError) throw dbError;

      // Update local status, then check enrollment
      const newStatuses: DocStatuses = { ...docStatuses, [docKey]: 'uploaded' };
      setDocStatuses(newStatuses);

      // Sync enrollment (ENROLLED / NOT ENROLLED)
      await syncEnrollmentStatus(newStatuses);

      Alert.alert('Uploaded ✓', `${docLabel} has been uploaded successfully.`);
    } catch (err: any) {
      setDocStatuses((prev) => ({ ...prev, [docKey]: 'not_uploaded' }));
      Alert.alert('Upload Failed', err.message ?? 'Something went wrong. Please try again.');
    }
  };

  const handleLogout = () => {
    Alert.alert('Logout', 'Are you sure you want to logout?', [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Logout', style: 'destructive', onPress: () => router.replace('/') },
    ]);
  };

  // ---------- Derived state ----------
  const uploadedCount = Object.values(docStatuses).filter((s) => s === 'uploaded').length;
  const enrollmentStatus: string = student?.is_accepted ?? 'PENDING';

  const isEnrolled    = enrollmentStatus === 'ENROLLED';
  const isNotEnrolled = enrollmentStatus === 'NOT ENROLLED';
  const isAccepted    = enrollmentStatus === 'ACCEPTED';
  const isRejected    = enrollmentStatus === 'REJECTED';
  const isPending     = enrollmentStatus === 'PENDING';

  // Docs can be uploaded if admin said yes (ACCEPTED / ENROLLED / NOT ENROLLED)
  const canUpload = isAcceptedOrEnrolled(enrollmentStatus);

  // ---------- Status card config ----------
  const statusConfig = isEnrolled
    ? { colors: ['#D1FAE5', '#ECFDF5'] as const, icon: 'check-circle' as const, iconColor: '#059669', label: 'ENROLLED', hint: 'Congratulations! All your documents are complete. You are officially enrolled.' }
    : isNotEnrolled
    ? { colors: ['#FEF3C7', '#FFFBEB'] as const, icon: 'exclamation-circle' as const, iconColor: '#D97706', label: 'NOT ENROLLED', hint: 'Some documents may be missing. Please complete all required uploads below.' }
    : isAccepted
    ? { colors: ['#DBEAFE', '#EFF6FF'] as const, icon: 'thumbs-up' as const, iconColor: '#2563EB', label: 'ACCEPTED', hint: 'Your application was accepted! Upload all 4 required documents to complete enrollment.' }
    : isRejected
    ? { colors: ['#FEE2E2', '#FEF2F2'] as const, icon: 'times-circle' as const, iconColor: '#DC2626', label: 'REJECTED', hint: 'Please contact the admin for more information.' }
    : { colors: ['#F3F4F6', '#F9FAFB'] as const, icon: 'hourglass-half' as const, iconColor: '#D97706', label: 'PENDING', hint: 'Your application is under review by the Admin.' };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#4F46E5" />
        <Text style={styles.loadingText}>Loading your dashboard...</Text>
      </View>
    );
  }

  return (
    <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>

      {/* ── Header ── */}
      <LinearGradient colors={['#1a1a2e', '#0f3460']} style={styles.header}>
        <View style={styles.headerTop}>
          <View style={styles.avatarCircle}>
            <Text style={styles.avatarText}>
              {student?.first_name?.[0]?.toUpperCase() ?? '?'}
            </Text>
          </View>
          <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout}>
            <FontAwesome name="sign-out" size={14} color="rgba(255,255,255,0.8)" />
            <Text style={styles.logoutText}>Logout</Text>
          </TouchableOpacity>
        </View>
        <Text style={styles.welcome}>Hello, {student?.first_name}!</Text>
        <Text style={styles.idText}>
          <FontAwesome name="id-badge" size={12} color="#a4b0be" /> {student?.student_id}
        </Text>
        {student?.course && (
          <View style={styles.coursePill}>
            <Text style={styles.coursePillText}>{student.course}</Text>
          </View>
        )}
      </LinearGradient>

      {/* ── Status Card ── */}
      <View style={styles.statusCard}>
        <LinearGradient colors={statusConfig.colors} style={styles.statusGradient}>
          <View style={styles.statusIcon}>
            {enrollmentLoading
              ? <ActivityIndicator size="small" color={statusConfig.iconColor} />
              : <FontAwesome name={statusConfig.icon} size={28} color={statusConfig.iconColor} />}
          </View>
          <View style={styles.statusInfo}>
            <Text style={styles.statusLabel}>Enrollment Status</Text>
            <Text style={[styles.statusValue, { color: statusConfig.iconColor }]}>
              {statusConfig.label}
            </Text>
            <Text style={styles.statusHint}>{statusConfig.hint}</Text>
          </View>
        </LinearGradient>
      </View>

      {/* ── Enrollment Checklist Banner (ACCEPTED only) ── */}
      {isAccepted && (
        <View style={styles.bannerCard}>
          <FontAwesome name="info-circle" size={16} color="#2563EB" />
          <Text style={styles.bannerText}>
            Upload all <Text style={{ fontWeight: '900' }}>4 required documents</Text> below to automatically become{' '}
            <Text style={{ fontWeight: '900', color: '#059669' }}>ENROLLED</Text>.
          </Text>
        </View>
      )}

      {/* ── Enrolled Celebration Banner ── */}
      {isEnrolled && (
        <LinearGradient colors={['#059669', '#10B981']} style={styles.celebrationCard}>
          <FontAwesome name="star" size={18} color="white" />
          <Text style={styles.celebrationText}>
            🎉 You are officially <Text style={{ fontWeight: '900' }}>ENROLLED</Text>! Welcome aboard.
          </Text>
        </LinearGradient>
      )}

      {/* ── Not Enrolled Warning Banner ── */}
      {isNotEnrolled && (
        <View style={styles.warningCard}>
          <FontAwesome name="warning" size={16} color="#D97706" />
          <Text style={styles.warningText}>
            You still have <Text style={{ fontWeight: '900' }}>{REQUIRED_DOCS.length - uploadedCount} document(s)</Text> missing. Complete all uploads to be enrolled.
          </Text>
        </View>
      )}

      {/* ── Documents Section ── */}
      <View style={styles.section}>
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Required Documents</Text>
          {canUpload && (
            <View style={[
              styles.progressBadge,
              uploadedCount === REQUIRED_DOCS.length && styles.progressBadgeDone,
            ]}>
              <Text style={styles.progressText}>{uploadedCount}/{REQUIRED_DOCS.length}</Text>
            </View>
          )}
        </View>

        {canUpload && (
          <View style={styles.progressBarWrap}>
            <View style={styles.progressBarBg}>
              <View style={[
                styles.progressBarFill,
                { width: `${(uploadedCount / REQUIRED_DOCS.length) * 100}%` as any },
                uploadedCount === REQUIRED_DOCS.length && styles.progressBarFillDone,
              ]} />
            </View>
            <Text style={styles.progressLabel}>
              {uploadedCount === REQUIRED_DOCS.length
                ? '✓ All documents uploaded — you are ENROLLED!'
                : `${REQUIRED_DOCS.length - uploadedCount} more to complete enrollment`}
            </Text>
          </View>
        )}

        {canUpload ? (
          <View style={styles.docGrid}>
            {REQUIRED_DOCS.map((doc) => {
              const status = docStatuses[doc.key] ?? 'not_uploaded';
              const isUploaded  = status === 'uploaded';
              const isUploading = status === 'uploading';

              return (
                <TouchableOpacity
                  key={doc.key}
                  style={[styles.docCard, isUploaded && styles.docCardDone]}
                  onPress={() => !isUploading && handleUpload(doc.key, doc.label)}
                  disabled={isUploading}
                  activeOpacity={0.8}
                >
                  {/* Badge */}
                  <View style={[styles.docStatusBadge, isUploaded ? styles.docBadgeDone : styles.docBadgePending]}>
                    <FontAwesome
                      name={isUploaded ? 'check' : 'clock-o'}
                      size={10}
                      color={isUploaded ? '#059669' : '#D97706'}
                    />
                    <Text style={[styles.docBadgeText, isUploaded ? { color: '#059669' } : { color: '#D97706' }]}>
                      {isUploaded ? 'Done' : 'Needed'}
                    </Text>
                  </View>

                  {/* Icon */}
                  <View style={[styles.docIcon, isUploaded && styles.docIconDone]}>
                    {isUploading
                      ? <ActivityIndicator size="small" color="#4F46E5" />
                      : <FontAwesome name={doc.icon as any} size={22} color={isUploaded ? '#059669' : '#4F46E5'} />
                    }
                  </View>

                  <Text style={[styles.docLabel, isUploaded && styles.docLabelDone]}>{doc.label}</Text>

                  {/* Action */}
                  <View style={[styles.uploadAction, isUploaded && styles.uploadActionDone]}>
                    <FontAwesome
                      name={isUploaded ? 'refresh' : 'cloud-upload'}
                      size={12}
                      color={isUploaded ? '#059669' : '#4F46E5'}
                    />
                    <Text style={[styles.uploadActionText, isUploaded && { color: '#059669' }]}>
                      {isUploading ? 'Uploading...' : isUploaded ? 'Re-upload' : 'Upload'}
                    </Text>
                  </View>
                </TouchableOpacity>
              );
            })}
          </View>
        ) : (
          /* Lock box for PENDING / REJECTED */
          <View style={styles.lockBox}>
            <View style={styles.lockIconWrap}>
              <FontAwesome name="lock" size={36} color="#9CA3AF" />
            </View>
            <Text style={styles.lockTitle}>Documents Locked</Text>
            <Text style={styles.lockText}>
              {isRejected
                ? 'Document upload is unavailable. Please contact the administration.'
                : 'Your enrollment is pending. Once the Admin accepts your application, you can upload your required documents here.'}
            </Text>
            {!isRejected && (
              <TouchableOpacity style={styles.refreshBtn} onPress={fetchStudentData}>
                <FontAwesome name="refresh" size={14} color="#4F46E5" />
                <Text style={styles.refreshText}>Refresh Status</Text>
              </TouchableOpacity>
            )}
          </View>
        )}
      </View>

      {/* ── Info Card ── */}
      <View style={styles.infoCard}>
        <FontAwesome name="info-circle" size={16} color="#4F46E5" />
        <Text style={styles.infoText}>
          Need help? Contact the Registrar's Office or email{' '}
          <Text style={{ fontWeight: '700', color: '#4F46E5' }}>registrar@aclc.edu.ph</Text>
        </Text>
      </View>

      <View style={{ height: 40 }} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F3F4F6' },

  loadingContainer: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#F3F4F6' },
  loadingText: { marginTop: 16, color: '#6B7280', fontSize: 15 },

  // ── Header ──
  header: {
    padding: 28, paddingTop: 60, paddingBottom: 36,
    borderBottomLeftRadius: 32, borderBottomRightRadius: 32,
  },
  headerTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 },
  avatarCircle: {
    width: 54, height: 54, borderRadius: 27,
    backgroundColor: 'rgba(255,255,255,0.2)',
    justifyContent: 'center', alignItems: 'center',
    borderWidth: 2, borderColor: 'rgba(255,255,255,0.4)',
  },
  avatarText: { color: 'white', fontSize: 22, fontWeight: '900' },
  logoutBtn: {
    flexDirection: 'row', alignItems: 'center', gap: 6,
    backgroundColor: 'rgba(255,255,255,0.1)',
    paddingVertical: 8, paddingHorizontal: 14,
    borderRadius: 20, borderWidth: 1, borderColor: 'rgba(255,255,255,0.2)',
  },
  logoutText: { color: 'rgba(255,255,255,0.8)', fontSize: 13, fontWeight: '600' },
  welcome: { fontSize: 28, fontWeight: '900', color: 'white' },
  idText: { color: '#a4b0be', marginTop: 6, fontSize: 13 },
  coursePill: {
    marginTop: 10, alignSelf: 'flex-start',
    backgroundColor: 'rgba(79,70,229,0.5)',
    paddingVertical: 5, paddingHorizontal: 14,
    borderRadius: 20, borderWidth: 1, borderColor: 'rgba(255,255,255,0.2)',
  },
  coursePillText: { color: 'white', fontWeight: '800', fontSize: 12, letterSpacing: 0.5 },

  // ── Status Card ──
  statusCard: {
    marginHorizontal: 16, marginTop: -20,
    borderRadius: 20, overflow: 'hidden',
    elevation: 8, shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.12, shadowRadius: 12,
  },
  statusGradient: { flexDirection: 'row', padding: 20, alignItems: 'flex-start' },
  statusIcon: { marginRight: 16, marginTop: 2 },
  statusInfo: { flex: 1 },
  statusLabel: { fontSize: 12, color: '#6B7280', fontWeight: '600', textTransform: 'uppercase', letterSpacing: 0.5 },
  statusValue: { fontSize: 20, fontWeight: '900', marginTop: 2 },
  statusHint: { fontSize: 13, color: '#6B7280', marginTop: 4, lineHeight: 18 },

  // ── Banners ──
  bannerCard: {
    flexDirection: 'row', alignItems: 'flex-start', gap: 10,
    margin: 16, marginBottom: 0,
    backgroundColor: '#DBEAFE', padding: 14, borderRadius: 14,
    borderWidth: 1, borderColor: '#BFDBFE',
  },
  bannerText: { flex: 1, fontSize: 13, color: '#1D4ED8', lineHeight: 18 },

  celebrationCard: {
    flexDirection: 'row', alignItems: 'center', gap: 10,
    margin: 16, marginBottom: 0,
    padding: 14, borderRadius: 14,
  },
  celebrationText: { flex: 1, fontSize: 13, color: 'white', lineHeight: 18 },

  warningCard: {
    flexDirection: 'row', alignItems: 'flex-start', gap: 10,
    margin: 16, marginBottom: 0,
    backgroundColor: '#FEF3C7', padding: 14, borderRadius: 14,
    borderWidth: 1, borderColor: '#FDE68A',
  },
  warningText: { flex: 1, fontSize: 13, color: '#92400E', lineHeight: 18 },

  // ── Section ──
  section: { margin: 16, marginTop: 20 },
  sectionHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 14 },
  sectionTitle: { fontSize: 18, fontWeight: '900', color: '#1F2937' },
  progressBadge: { backgroundColor: '#4F46E5', paddingVertical: 4, paddingHorizontal: 12, borderRadius: 20 },
  progressBadgeDone: { backgroundColor: '#059669' },
  progressText: { color: 'white', fontWeight: '800', fontSize: 13 },

  progressBarWrap: { marginBottom: 16 },
  progressBarBg: { height: 8, backgroundColor: '#E5E7EB', borderRadius: 4, overflow: 'hidden', marginBottom: 6 },
  progressBarFill: { height: 8, backgroundColor: '#4F46E5', borderRadius: 4 },
  progressBarFillDone: { backgroundColor: '#059669' },
  progressLabel: { fontSize: 12, color: '#6B7280', fontWeight: '600' },

  // ── Doc Cards ──
  docGrid: { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between', gap: 12 },
  docCard: {
    backgroundColor: 'white', width: '48%', padding: 16,
    borderRadius: 18, alignItems: 'center',
    elevation: 3, shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.08, shadowRadius: 6,
    borderWidth: 1.5, borderColor: '#E5E7EB', position: 'relative',
  },
  docCardDone: { borderColor: '#A7F3D0', backgroundColor: '#F0FDF4' },
  docStatusBadge: {
    flexDirection: 'row', alignItems: 'center', gap: 4,
    position: 'absolute', top: 10, right: 10,
    paddingVertical: 3, paddingHorizontal: 7, borderRadius: 10,
  },
  docBadgeDone: { backgroundColor: '#D1FAE5' },
  docBadgePending: { backgroundColor: '#FEF3C7' },
  docBadgeText: { fontSize: 10, fontWeight: '700' },
  docIcon: {
    width: 52, height: 52, borderRadius: 26,
    backgroundColor: '#EEF2FF',
    justifyContent: 'center', alignItems: 'center',
    marginTop: 8, marginBottom: 10,
  },
  docIconDone: { backgroundColor: '#D1FAE5' },
  docLabel: { fontSize: 12, fontWeight: '700', textAlign: 'center', color: '#374151', lineHeight: 16, marginBottom: 12 },
  docLabelDone: { color: '#065F46' },
  uploadAction: {
    flexDirection: 'row', alignItems: 'center', gap: 6,
    backgroundColor: '#EEF2FF', paddingVertical: 7, paddingHorizontal: 14, borderRadius: 20,
  },
  uploadActionDone: { backgroundColor: '#D1FAE5' },
  uploadActionText: { fontSize: 12, fontWeight: '700', color: '#4F46E5' },

  // ── Lock Box ──
  lockBox: {
    padding: 36, alignItems: 'center',
    backgroundColor: 'white', borderRadius: 20,
    borderWidth: 1.5, borderColor: '#E5E7EB',
  },
  lockIconWrap: {
    width: 72, height: 72, borderRadius: 36,
    backgroundColor: '#F3F4F6',
    justifyContent: 'center', alignItems: 'center', marginBottom: 16,
  },
  lockTitle: { fontSize: 18, fontWeight: '800', color: '#374151', marginBottom: 10 },
  lockText: { textAlign: 'center', color: '#6B7280', lineHeight: 20, fontSize: 14 },
  refreshBtn: {
    flexDirection: 'row', alignItems: 'center', gap: 8,
    marginTop: 20, backgroundColor: '#EEF2FF',
    paddingVertical: 10, paddingHorizontal: 20, borderRadius: 20,
  },
  refreshText: { color: '#4F46E5', fontWeight: '700', fontSize: 14 },

  // ── Info Card ──
  infoCard: {
    flexDirection: 'row', alignItems: 'flex-start', gap: 10,
    margin: 16, marginTop: 0,
    backgroundColor: '#EEF2FF', padding: 16, borderRadius: 14,
  },
  infoText: { flex: 1, fontSize: 13, color: '#4B5563', lineHeight: 18 },
});