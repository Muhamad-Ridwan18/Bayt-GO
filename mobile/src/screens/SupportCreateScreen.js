import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TextInput } from 'react-native';
import ScreenHeader from '../components/ScreenHeader';
import AttachmentPicker from '../components/AttachmentPicker';
import AuthInput from '../components/AuthInput';
import { fetchSupportMeta, createSupportTicket } from '../api/support';
import { useAuth } from '../context/AuthContext';
import { Button, Card, InlineAlert, SkeletonList } from '../ui';
import { ChipPicker } from '../features/support/SupportFormParts';
import { notifySuccessThen } from '../utils/feedback';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { useLocale } from '../utils/locale';

export default function SupportCreateScreen({ navigation }) {
  const { token, isVerifiedMuthowif } = useAuth();
  const locale = useLocale();
  const isEn = locale === 'en';

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

  const headerTitle = isEn ? 'New ticket' : 'Tiket baru';
  const headerSubtitle = isEn
    ? 'Describe the issue so we can help quickly.'
    : 'Jelaskan masalah agar kami bisa membantu lebih cepat.';
  const validationSubject = isEn ? 'Subject is required.' : 'Subjek wajib diisi.';
  const validationMessage = isEn ? 'Message is required.' : 'Pesan wajib diisi.';
  const validationCategoryPriority = isEn
    ? 'Category and priority are required.'
    : 'Kategori dan prioritas wajib dipilih.';
  const submitSuccessMessage = isEn ? 'Ticket created.' : 'Tiket bantuan berhasil dibuat.';
  const submitErrorFallback = isEn ? 'Failed to create ticket' : 'Gagal membuat tiket';

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
    if (!subject.trim()) { setError(validationSubject); return; }
    if (!body.trim()) { setError(validationMessage); return; }
    if (!category || !priority) { setError(validationCategoryPriority); return; }

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
        submitSuccessMessage,
        () => navigation.replace('SupportDetail', { ticketId: data.ticket?.id }),
      );
    } catch (err) {
      setError(err.message || submitErrorFallback);
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <ScreenHeader
        variant={isVerifiedMuthowif ? 'brand' : 'light'}
        title={headerTitle}
        subtitle={headerSubtitle}
        onBack={() => navigation.goBack()}
      />

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
            label={isEn ? 'Subject' : 'Subjek'}
            icon="document-text-outline"
            value={subject}
            onChangeText={setSubject}
            placeholder={isEn ? 'Briefly describe the issue' : 'Ringkasan masalah Anda'}
            maxLength={160}
          />

          <ChipPicker label={isEn ? 'Category *' : 'Kategori *'} options={categories} value={category} onChange={setCategory} />
          <ChipPicker label={isEn ? 'Priority *' : 'Prioritas *'} options={priorities} value={priority} onChange={setPriority} />

          <Text style={styles.label}>{isEn ? 'Message *' : 'Pesan *'}</Text>
          <Text style={styles.hint}>
            {isEn
              ? 'Include steps to reproduce, expected vs actual behaviour, and avoid sharing passwords or full card numbers.'
              : 'Tuliskan langkah untuk mengulangi masalah (jika bisa), apa yang Anda harapkan vs yang terjadi, dan hindari menyertakan kata sandi atau nomor kartu lengkap.'}
          </Text>
          <TextInput
            style={styles.textarea}
            value={body}
            onChangeText={setBody}
            placeholder={isEn ? 'Describe your issue or question in detail...' : 'Jelaskan masalah atau pertanyaan Anda secara detail...'}
            placeholderTextColor={colors.textMuted}
            multiline
            maxLength={12000}
            textAlignVertical="top"
          />

          <AttachmentPicker
            label={isEn ? 'Attachments (optional)' : 'Lampiran (opsional)'}
            hint={isEn ? 'Up to 5 files: JPG, PNG, GIF, WebP or PDF — max 5 MB each.' : 'Maks. 5 berkas: JPG, PNG, GIF, WebP atau PDF — tiap berkas maks. 5 MB'}
            files={attachments}
            onChange={setAttachments}
            disabled={loading}
          />

          <InlineAlert variant="warning">
            {isEn ? 'Do not paste passwords or complete payment-card details.' : 'Jangan menyisipkan kata sandi atau data kartu lengkap.'}
          </InlineAlert>

          <Button label={isEn ? 'Submit ticket' : 'Kirim Tiket'} onPress={handleSubmit} loading={loading} />
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
  hint: {
    marginTop: -spacing.xs,
    marginBottom: spacing.sm,
    ...typography.small,
    color: colors.textSecondary,
    lineHeight: 18,
  },
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
