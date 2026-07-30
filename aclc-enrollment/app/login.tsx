import React, { useState } from 'react';
import {
  View, Text, TextInput, TouchableOpacity, StyleSheet,
  Alert, ScrollView, StatusBar, ActivityIndicator, Image,
  Platform, Modal,
} from 'react-native';
import { useRouter } from 'expo-router';
import { LinearGradient } from 'expo-linear-gradient';
import { FontAwesome } from '@expo/vector-icons';
import DateTimePicker, { DateTimePickerEvent } from '@react-native-community/datetimepicker';
import { supabase } from '../lib/supabase';

export default function StudentLogin() {
  const [isRegistering, setIsRegistering] = useState(false);
  const [loading, setLoading] = useState(false);
  const router = useRouter();

  // Login fields
  const [studentIdInput, setStudentIdInput] = useState('');

  // Register fields
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [regEmail, setRegEmail] = useState('');
  const [course, setCourse] = useState('');
  const [showCoursePicker, setShowCoursePicker] = useState(false);
  const [dob, setDob] = useState<Date | null>(null);
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [tempDate, setTempDate] = useState<Date>(new Date(2003, 0, 1));
  const [yearLevel, setYearLevel] = useState('');
  const [showYearPicker, setShowYearPicker] = useState(false);
  const [semester, setSemester] = useState('');
  const [showSemPicker, setShowSemPicker] = useState(false);
  const [studentType, setStudentType] = useState('');
  const [showTypePicker, setShowTypePicker] = useState(false);
  const [address, setAddress] = useState('');

  const courses = ['BSIT', 'BSCS', 'BSHM', 'BSBA'];
  const yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
  const semesters = ['1st Semester', '2nd Semester'];
  const studentTypes = ['Regular', 'Irregular', 'Tesda'];

  const formatDate = (date: Date): string => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  };

  const openDatePicker = () => {
    setTempDate(dob || new Date(2003, 0, 1));
    setShowDatePicker(true);
  };

  const onDateChange = (_event: DateTimePickerEvent, selectedDate?: Date) => {
    if (Platform.OS === 'android') {
      setShowDatePicker(false);
      if (_event.type === 'set' && selectedDate) {
        setDob(selectedDate);
      }
    } else {
      if (selectedDate) {
        setTempDate(selectedDate);
      }
    }
  };

  const confirmIOSDate = () => {
    setDob(tempDate);
    setShowDatePicker(false);
  };

  const cancelIOSDate = () => {
    setShowDatePicker(false);
  };

  // ─── REGISTER ────────────────────────────────────────────────
  const handleRegister = async () => {
    if (!firstName || !lastName || !regEmail || !course || !dob || !yearLevel || !semester || !studentType) {
      Alert.alert('Required', 'Please fill in all fields.');
      return;
    }

    setLoading(true);

    const { data: existing } = await supabase
      .from('students')
      .select('email')
      .eq('email', regEmail.toLowerCase().trim())
      .maybeSingle();

    if (existing) {
      Alert.alert('Already Registered', 'This email already has an application. Please login instead.');
      setLoading(false);
      return;
    }

    const newGeneratedId = 'STU' + Math.floor(100000 + Math.random() * 900000);

    const { error } = await supabase.from('students').insert([{
      student_id: newGeneratedId,
      first_name: firstName,
      last_name: lastName,
      email: regEmail.toLowerCase().trim(),
      course: course,
      date_of_birth: formatDate(dob),
      year_level: yearLevel,
      semester: semester,
      student_type: studentType,
      address: address,
      is_accepted: 'PENDING',
      enrollment_date: new Date().toISOString(),
    }]);

    if (error) {
      Alert.alert('Registration Failed', error.message);
    } else {
      Alert.alert(
        '✅ Application Submitted!',
        `Your Student ID is:\n\n${newGeneratedId}\n\nSave this ID! You will use it to log in after Admin accepts your application.`,
        [{
          text: 'OK', onPress: () => {
            setIsRegistering(false);
            setFirstName(''); setLastName(''); setRegEmail('');
            setCourse(''); setDob(null); setYearLevel('');
            setSemester(''); setStudentType(''); setAddress('');
          }
        }]
      );
    }

    setLoading(false);
  };

  // ─── LOGIN ────────────────────────────────────────────────────
  const handleLogin = async () => {
    if (!studentIdInput) {
      Alert.alert('Required', 'Please enter your Student ID.');
      return;
    }

    setLoading(true);

    const { data: student, error } = await supabase
      .from('students')
      .select('*')
      .eq('student_id', studentIdInput.toUpperCase().trim())
      .maybeSingle();

    if (error || !student) {
      Alert.alert(
        '❌ Student ID Not Found',
        'No account found with this Student ID.\n\nMake sure you type it correctly (e.g. STU847291 — no dash).'
      );
      setLoading(false);
      return;
    }

    if (student.is_accepted === 'PENDING') {
      Alert.alert(
        '⏳ Application Pending',
        'Your application has not been accepted yet by the Admin. Please wait for approval before logging in.'
      );
      setLoading(false);
      return;
    }

    if (student.is_accepted === 'DELETED') {
      Alert.alert(
        '⛔ Account Unavailable',
        'Your account has been removed. Please contact the admin for assistance.'
      );
      setLoading(false);
      return;
    }

    router.replace({ pathname: '/dashboard', params: { student_id: student.student_id } });
    setLoading(false);
  };

  const handleAuth = () => {
    if (isRegistering) handleRegister();
    else handleLogin();
  };

  // ─── REUSABLE DROPDOWN ───────────────────────────────────────
  const Dropdown = ({
    label, value, placeholder, options, show, onToggle, onSelect,
  }: {
    label: string; value: string; placeholder: string;
    options: string[]; show: boolean;
    onToggle: () => void; onSelect: (v: string) => void;
  }) => (
    <View style={styles.fieldGroup}>
      <Text style={styles.inputLabel}>{label}</Text>
      <TouchableOpacity style={styles.courseSelector} onPress={onToggle}>
        <Text style={value ? styles.courseSelectorText : styles.coursePlaceholder}>
          {value || placeholder}
        </Text>
        <FontAwesome name={show ? 'chevron-up' : 'chevron-down'} size={12} color="#747d8c" />
      </TouchableOpacity>
      {show && (
        <View style={styles.courseDropdown}>
          {options.map((opt) => (
            <TouchableOpacity
              key={opt}
              style={[styles.courseOption, value === opt && styles.courseOptionActive]}
              onPress={() => onSelect(opt)}
            >
              <Text style={[styles.courseOptionText, value === opt && styles.courseOptionTextActive]}>
                {opt}
              </Text>
            </TouchableOpacity>
          ))}
        </View>
      )}
    </View>
  );

  return (
    <LinearGradient colors={['#1a1a2e', '#16213e', '#0f3460']} style={styles.container}>
      <StatusBar barStyle="light-content" />

      <TouchableOpacity style={styles.backBtn} onPress={() => router.back()}>
        <FontAwesome name="arrow-left" size={14} color="white" />
        <Text style={styles.backText}>Back</Text>
      </TouchableOpacity>

      <ScrollView contentContainerStyle={styles.scrollContainer} keyboardShouldPersistTaps="handled">
        <View style={styles.loginBox}>

          <View style={styles.logoContainer}>
            <Image source={require('../assets/images/logo.png')} style={styles.logo} />
          </View>

          <Text style={styles.headerTitle}>
            {isRegistering ? 'Apply for Enrollment' : 'Student Portal'}
          </Text>
          <Text style={styles.headerSubtitle}>
            {isRegistering
              ? 'Fill in your details to submit an application'
              : 'Enter your Student ID to access your portal'}
          </Text>

          {/* Toggle Tabs */}
          <View style={styles.tabRow}>
            <TouchableOpacity
              style={[styles.tab, !isRegistering && styles.tabActive]}
              onPress={() => setIsRegistering(false)}
            >
              <Text style={[styles.tabText, !isRegistering && styles.tabTextActive]}>Login</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.tab, isRegistering && styles.tabActive]}
              onPress={() => setIsRegistering(true)}
            >
              <Text style={[styles.tabText, isRegistering && styles.tabTextActive]}>Register</Text>
            </TouchableOpacity>
          </View>

          <View style={styles.inputContainer}>

            {/* ── LOGIN FORM ── */}
            {!isRegistering && (
              <View style={styles.fieldGroup}>
                <Text style={styles.inputLabel}>Student ID</Text>
                <TextInput
                  style={styles.input}
                  placeholder="e.g. STU123456"
                  placeholderTextColor="#a4b0be"
                  value={studentIdInput}
                  onChangeText={setStudentIdInput}
                  autoCapitalize="characters"
                />
                <Text style={styles.hintText}>
                  💡 No dash ( - ) in the ID. Example: STU847291
                </Text>
              </View>
            )}

            {/* ── REGISTER FORM ── */}
            {isRegistering && (
              <>
                <View style={styles.fieldGroup}>
                  <Text style={styles.inputLabel}>First Name</Text>
                  <TextInput
                    style={styles.input}
                    placeholder="e.g. Juan"
                    placeholderTextColor="#a4b0be"
                    value={firstName}
                    onChangeText={setFirstName}
                  />
                </View>

                <View style={styles.fieldGroup}>
                  <Text style={styles.inputLabel}>Last Name</Text>
                  <TextInput
                    style={styles.input}
                    placeholder="e.g. Dela Cruz"
                    placeholderTextColor="#a4b0be"
                    value={lastName}
                    onChangeText={setLastName}
                  />
                </View>

                <View style={styles.fieldGroup}>
                  <Text style={styles.inputLabel}>Email Address</Text>
                  <TextInput
                    style={styles.input}
                    placeholder="student@example.com"
                    placeholderTextColor="#a4b0be"
                    value={regEmail}
                    onChangeText={setRegEmail}
                    keyboardType="email-address"
                    autoCapitalize="none"
                  />
                </View>

                {/* ── BIRTHDAY DATE PICKER ── */}
                <View style={styles.fieldGroup}>
                  <Text style={styles.inputLabel}>Birthday</Text>
                  <TouchableOpacity
                    style={styles.courseSelector}
                    onPress={openDatePicker}
                  >
                    <Text style={dob ? styles.courseSelectorText : styles.coursePlaceholder}>
                      {dob ? formatDate(dob) : 'Select your birthday'}
                    </Text>
                    <FontAwesome name="calendar" size={14} color="#747d8c" />
                  </TouchableOpacity>
                </View>

                {/* Android: native dialog (auto-visible) */}
                {showDatePicker && Platform.OS === 'android' && (
                  <DateTimePicker
                    value={tempDate}
                    mode="date"
                    display="default"
                    maximumDate={new Date()}
                    minimumDate={new Date(1950, 0, 1)}
                    onChange={onDateChange}
                  />
                )}

                {/* iOS: Modal overlay so picker is always visible */}
                {Platform.OS === 'ios' && (
                  <Modal
                    transparent
                    animationType="slide"
                    visible={showDatePicker}
                    onRequestClose={cancelIOSDate}
                  >
                    <View style={styles.modalOverlay}>
                      <View style={styles.modalContent}>
                        <View style={styles.modalHeader}>
                          <TouchableOpacity onPress={cancelIOSDate}>
                            <Text style={styles.modalCancelText}>Cancel</Text>
                          </TouchableOpacity>
                          <Text style={styles.modalTitle}>Select Birthday</Text>
                          <TouchableOpacity onPress={confirmIOSDate}>
                            <Text style={styles.modalDoneText}>Done</Text>
                          </TouchableOpacity>
                        </View>
                        <DateTimePicker
                          value={tempDate}
                          mode="date"
                          display="spinner"
                          maximumDate={new Date()}
                          minimumDate={new Date(1950, 0, 1)}
                          onChange={onDateChange}
                          style={styles.iosPicker}
                          textColor="#000000"
                        />
                      </View>
                    </View>
                  </Modal>
                )}

                <Dropdown
                  label="Year Level"
                  value={yearLevel}
                  placeholder="Select year level"
                  options={yearLevels}
                  show={showYearPicker}
                  onToggle={() => setShowYearPicker(!showYearPicker)}
                  onSelect={(v) => { setYearLevel(v); setShowYearPicker(false); }}
                />

                <Dropdown
                  label="Semester"
                  value={semester}
                  placeholder="Select semester"
                  options={semesters}
                  show={showSemPicker}
                  onToggle={() => setShowSemPicker(!showSemPicker)}
                  onSelect={(v) => { setSemester(v); setShowSemPicker(false); }}
                />

                <Dropdown
                  label="Student Type"
                  value={studentType}
                  placeholder="Select student type"
                  options={studentTypes}
                  show={showTypePicker}
                  onToggle={() => setShowTypePicker(!showTypePicker)}
                  onSelect={(v) => { setStudentType(v); setShowTypePicker(false); }}
                />

                <Dropdown
                  label="Course"
                  value={course}
                  placeholder="Select your course"
                  options={courses}
                  show={showCoursePicker}
                  onToggle={() => setShowCoursePicker(!showCoursePicker)}
                  onSelect={(v) => { setCourse(v); setShowCoursePicker(false); }}
                />

                <View style={styles.fieldGroup}>
                  <Text style={styles.inputLabel}>Address</Text>
                  <TextInput
                    style={[styles.input, styles.textArea]}
                    placeholder="Complete address"
                    placeholderTextColor="#a4b0be"
                    value={address}
                    onChangeText={setAddress}
                    multiline
                    numberOfLines={3}
                  />
                </View>
              </>
            )}
          </View>

          <TouchableOpacity style={styles.btnPrimary} onPress={handleAuth} disabled={loading}>
            <LinearGradient
              colors={['#4F46E5', '#7C3AED']}
              style={styles.btnGradient}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
            >
              {loading
                ? <ActivityIndicator color="#fff" />
                : (
                  <>
                    <FontAwesome name={isRegistering ? 'paper-plane' : 'sign-in'} size={16} color="white" />
                    <Text style={styles.btnText}>{isRegistering ? 'Submit Application' : 'Login'}</Text>
                  </>
                )}
            </LinearGradient>
          </TouchableOpacity>

          <View style={styles.toggleContainer}>
            <Text style={styles.toggleTextNormal}>
              {isRegistering ? 'Already have an account? ' : 'New student? '}
            </Text>
            <TouchableOpacity onPress={() => setIsRegistering(!isRegistering)}>
              <Text style={styles.toggleTextBold}>
                {isRegistering ? 'Login here' : 'Register here'}
              </Text>
            </TouchableOpacity>
          </View>

        </View>
      </ScrollView>
    </LinearGradient>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  scrollContainer: { flexGrow: 1, justifyContent: 'center', padding: 20, paddingTop: 80 },

  backBtn: {
    position: 'absolute', top: 50, left: 20, zIndex: 10,
    flexDirection: 'row', alignItems: 'center', gap: 8,
    backgroundColor: 'rgba(255,255,255,0.15)',
    paddingVertical: 10, paddingHorizontal: 16,
    borderRadius: 30, borderWidth: 1, borderColor: 'rgba(255,255,255,0.2)',
  },
  backText: { color: 'white', fontWeight: '700', fontSize: 13 },

  loginBox: {
    backgroundColor: '#ffffff', borderRadius: 28, padding: 28,
    alignItems: 'center', elevation: 20, shadowColor: '#4F46E5',
    shadowOffset: { width: 0, height: 12 }, shadowOpacity: 0.2, shadowRadius: 24,
  },
  logoContainer: {
    width: 80, height: 80, borderRadius: 40,
    backgroundColor: '#f1f2f6', justifyContent: 'center',
    alignItems: 'center', marginBottom: 16,
  },
  logo: { width: 60, height: 60, resizeMode: 'contain' },
  headerTitle: { fontSize: 24, fontWeight: '900', color: '#1a1a2e', marginBottom: 6 },
  headerSubtitle: { fontSize: 13, color: '#747d8c', textAlign: 'center', marginBottom: 20 },

  tabRow: {
    flexDirection: 'row', backgroundColor: '#f1f2f6',
    borderRadius: 14, padding: 4, width: '100%', marginBottom: 20,
  },
  tab: { flex: 1, paddingVertical: 10, alignItems: 'center', borderRadius: 10 },
  tabActive: { backgroundColor: '#4F46E5' },
  tabText: { fontSize: 14, fontWeight: '700', color: '#747d8c' },
  tabTextActive: { color: 'white' },

  inputContainer: { width: '100%' },
  fieldGroup: { marginBottom: 14 },
  inputLabel: { fontSize: 12, fontWeight: '800', color: '#57606f', marginBottom: 6, letterSpacing: 0.5 },
  input: {
    width: '100%', paddingVertical: 14, paddingHorizontal: 16,
    backgroundColor: '#f8f9fa', borderRadius: 12, fontSize: 15,
    color: '#2f3542', borderWidth: 1.5, borderColor: '#e9ecef',
  },
  textArea: { height: 80, textAlignVertical: 'top' },
  hintText: { fontSize: 11, color: '#9CA3AF', marginTop: 5, fontStyle: 'italic' },

  courseSelector: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    paddingVertical: 14, paddingHorizontal: 16,
    backgroundColor: '#f8f9fa', borderRadius: 12, borderWidth: 1.5, borderColor: '#e9ecef',
  },
  courseSelectorText: { fontSize: 15, color: '#2f3542' },
  coursePlaceholder: { fontSize: 15, color: '#a4b0be' },
  courseDropdown: {
    backgroundColor: 'white', borderRadius: 12, marginTop: 6,
    borderWidth: 1, borderColor: '#e9ecef', overflow: 'hidden',
    elevation: 4, shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.1, shadowRadius: 8,
  },
  courseOption: { paddingVertical: 14, paddingHorizontal: 16 },
  courseOptionActive: { backgroundColor: '#EEF2FF' },
  courseOptionText: { fontSize: 15, color: '#374151', fontWeight: '600' },
  courseOptionTextActive: { color: '#4F46E5', fontWeight: '800' },

  // ── Modal Date Picker Styles ──
  modalOverlay: {
    flex: 1,
    justifyContent: 'flex-end',
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
  },
  modalContent: {
    backgroundColor: '#ffffff',
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    paddingBottom: 30,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#e9ecef',
  },
  modalTitle: {
    fontSize: 17,
    fontWeight: '700',
    color: '#1a1a2e',
  },
  modalCancelText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#747d8c',
  },
  modalDoneText: {
    fontSize: 16,
    fontWeight: '700',
    color: '#4F46E5',
  },
  iosPicker: {
    height: 220,
    backgroundColor: '#ffffff',
  },

  btnPrimary: { width: '100%', borderRadius: 14, overflow: 'hidden', marginTop: 8 },
  btnGradient: {
    flexDirection: 'row', alignItems: 'center',
    justifyContent: 'center', gap: 10, paddingVertical: 16,
  },
  btnText: { color: '#ffffff', fontWeight: '800', fontSize: 16 },

  toggleContainer: { flexDirection: 'row', marginTop: 20 },
  toggleTextNormal: { fontSize: 14, color: '#747d8c' },
  toggleTextBold: { fontSize: 14, fontWeight: '800', color: '#4F46E5' },
});
