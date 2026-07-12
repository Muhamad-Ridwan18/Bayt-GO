import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TextInput } from 'react-native';
import ScreenHeader from '../components/ScreenHeader';
import AttachmentPicker from '../components/AttachmentPicker';
import AuthInput from '../components/AuthInput';
import { fetchSupportMeta, createSupportTicket } from '../api/support';
import { useAuth } from '../context/AuthContext';
import { Button, Card, SkeletonList } from '../ui';
import { ChipPicker } from '../features/support/SupportFormParts';
import { notifySuccessThen } from '../utils/feedback';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';

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
    if (!subject.trim()) { setError('Subjek wajib diisi.'); return; }
    if (!body.trim()) { setError('Pesan wajib diisi.'); return; }
    if (!category || !priority) { setError('Kategori dan prioritas wajib dipilih.'); return; }

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
      notifySuccessThen(
        navigation,
        'Tiket bantuan berhasil dibuat.',
        () => navigation.replace('SupportDetail', { ticketId: data.ticket?.id }),
      );
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
        <SkeletonList count={4} style={styles.skeleton} />
      ) : (
        <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
          {error ? (
            <Card style={styles.errorCard} padding={spacing.md} elevated={false}>
              <Text style={styles.errorText}>{error}</Text>
            </Card>
          ) : null}

          <AuthInput
            label="Subjek"
            icon="document-text-outline"
            value={subject}
            onChangeText={setSubject}
            placeholder="Ringkasan masalah Anda"
            maxLength={160}
          />

          <ChipPicker label="Kategori *" options={categories} value={category} onChange={setCategory} />
          <ChipPicker label="Prioritas *" options={priorities} value={priority} onChange={setPriority} />

          <Text style={styles.label}>Pesan *</Text>
          <TextInput
            style={styles.textarea}
            value={body}
            onChangeText={setBody}
            placeholder="Jelaskan masalah atau pertanyaan Anda secara detail..."
            placeholderTextColor={colors.textMuted}
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

          <Button label="Kirim Tiket" onPress={handleSubmit} loading={loading} />
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  skeleton: { padding: layout.screenPadding, paddingTop: spacing.lg },
  scroll: { padding: layout.screenPadding, paddingBottom: spacing['3xl'] },
  errorCard: { backgroundColor: colors.errorLight, borderColor: '#FECACA', marginBottom: spacing.lg },
  errorText: { ...typography.caption, color: colors.error, fontWeight: '600' },
  label: { ...typography.label, color: colors.textSecondary, marginBottom: spacing.sm },
  textarea: {
    minHeight: 140,
    backgroundColor: colors.card,
    borderRadius: radius.sm,
    borderWidth: 1,
    borderColor: colors.border,
    padding: spacing.lg,
    ...typography.caption,
    color: colors.textPrimary,
    marginBottom: spacing.xl,
  },
});
