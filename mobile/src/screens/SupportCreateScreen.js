import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
  TextInput,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import ScreenHeader from '../components/ScreenHeader';
import AttachmentPicker from '../components/AttachmentPicker';
import AuthInput from '../components/AuthInput';
import { fetchSupportMeta, createSupportTicket } from '../api/support';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

function ChipPicker({ label, options, value, onChange }) {
  return (
    <View style={styles.field}>
      <Text style={styles.label}>{label}</Text>
      <View style={styles.chipList}>
        {options.map((item) => {
          const active = value === item.value;
          return (
            <TouchableOpacity
              key={item.value}
              style={[styles.chip, active && styles.chipActive]}
              onPress={() => onChange(item.value)}
            >
              <Text style={[styles.chipText, active && styles.chipTextActive]}>{item.label}</Text>
            </TouchableOpacity>
          );
        })}
      </View>
    </View>
  );
}

export default function SupportCreateScreen({ navigation }) {
  const { token } = useAuth();

  const [metaLoading, setMetaLoading] = useState(true);
  const [categories, setCategories] = useState([]);
  const [priorities, setPriorities] = useState([]);
  const [subject, setSubject] = useState('');
  const [category, setCategory] = useState('');
  const [priority, setPriority] = useState('');
  const [body, setBody] = useState('');
  const [attachments, setAttachments] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    (async () => {
      try {
        const data = await fetchSupportMeta(token);
        const cats = data.categories || [];
        const prios = data.priorities || [];
        setCategories(cats);
        setPriorities(prios);
        if (cats[0]) setCategory(cats[0].value);
        if (prios[0]) setPriority(prios[0].value);
      } catch (err) {
        setError(err.message || 'Gagal memuat opsi tiket');
      } finally {
        setMetaLoading(false);
      }
    })();
  }, [token]);

  const handleSubmit = async () => {
    if (!subject.trim()) {
      setError('Subjek wajib diisi.');
      return;
    }
    if (!body.trim()) {
      setError('Pesan wajib diisi.');
      return;
    }
    if (!category || !priority) {
      setError('Kategori dan prioritas wajib dipilih.');
      return;
    }

    setLoading(true);
    setError('');
    try {
      const data = await createSupportTicket(token, {
        subject: subject.trim(),
        category,
        priority,
        body: body.trim(),
        attachments,
      });
      Alert.alert('Berhasil', 'Tiket bantuan berhasil dibuat.', [
        {
          text: 'OK',
          onPress: () => navigation.replace('SupportDetail', { ticketId: data.ticket?.id }),
        },
      ]);
    } catch (err) {
      setError(err.message || 'Gagal membuat tiket');
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <ScreenHeader title="Buat Tiket" subtitle="Ajukan bantuan ke tim Bayt-GO" onBack={() => navigation.goBack()} />

      {metaLoading ? (
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      ) : (
        <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
          {error ? <Text style={styles.error}>{error}</Text> : null}

          <AuthInput
            label="Subjek"
            icon="document-text-outline"
            value={subject}
            onChangeText={setSubject}
            placeholder="Ringkasan masalah Anda"
            maxLength={160}
          />

          <ChipPicker
            label="Kategori *"
            options={categories}
            value={category}
            onChange={setCategory}
          />

          <ChipPicker
            label="Prioritas *"
            options={priorities}
            value={priority}
            onChange={setPriority}
          />

          <Text style={styles.label}>Pesan *</Text>
          <TextInput
            style={styles.textarea}
            value={body}
            onChangeText={setBody}
            placeholder="Jelaskan masalah atau pertanyaan Anda secara detail..."
            placeholderTextColor={colors.slate400}
            multiline
            maxLength={12000}
            textAlignVertical="top"
          />

          <AttachmentPicker
            label="Lampiran (opsional)"
            hint="Maks. 5 file — foto atau PDF"
            files={attachments}
            onChange={setAttachments}
            disabled={loading}
          />

          <TouchableOpacity style={styles.submitBtn} onPress={handleSubmit} disabled={loading} activeOpacity={0.9}>
            <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.submitGradient}>
              {loading ? (
                <ActivityIndicator color={colors.white} />
              ) : (
                <Text style={styles.submitText}>Kirim Tiket</Text>
              )}
            </LinearGradient>
          </TouchableOpacity>
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  scroll: { padding: 20, paddingBottom: 32 },
  loader: { marginTop: 40 },
  error: {
    backgroundColor: '#FEF2F2',
    color: '#B91C1C',
    padding: 12,
    borderRadius: 12,
    fontSize: 13,
    fontWeight: '600',
    marginBottom: 16,
  },
  field: { marginBottom: 16 },
  label: { fontSize: 12, fontWeight: '800', color: colors.slate600, marginBottom: 8 },
  chipList: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  chip: {
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 8,
    backgroundColor: colors.white,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  chipActive: { backgroundColor: colors.emerald50, borderColor: colors.baytgo },
  chipText: { fontSize: 12, fontWeight: '700', color: colors.slate700 },
  chipTextActive: { color: colors.baytgo },
  textarea: {
    minHeight: 140,
    backgroundColor: colors.white,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
    padding: 14,
    fontSize: 14,
    fontWeight: '500',
    color: colors.slate900,
    marginBottom: 20,
  },
  submitBtn: { borderRadius: 16, overflow: 'hidden' },
  submitGradient: { paddingVertical: 16, alignItems: 'center' },
  submitText: { color: colors.white, fontSize: 15, fontWeight: '800' },
});
