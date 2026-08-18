import React, { useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TextInput } from 'react-native';
import ScreenHeader from '../components/ScreenHeader';
import StarRatingPicker from '../components/StarRatingPicker';
import { completeBooking, submitReview } from '../api/bookings';
import { useAuth } from '../context/AuthContext';
import { Button, Card, InlineAlert } from '../ui';
import { notifySuccessThen } from '../utils/feedback';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { useLocale } from '../utils/locale';

export default function BookingRatingScreen({ navigation, route }) {
  const { token } = useAuth();
  const locale = useLocale(); const isEn = locale === 'en';
  const { bookingId, mode = 'review', initialRating = 5, initialComment = '' } = route.params;

  const isComplete = mode === 'complete';
  const [rating, setRating] = useState(initialRating);
  const [comment, setComment] = useState(initialComment);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async () => {
    if (rating < 1) {
      setError(isEn ? 'Select a rating from 1–5 stars.' : 'Pilih rating 1–5 bintang.');
      return;
    }

    setLoading(true);
    setError('');
    try {
      if (isComplete) {
        await completeBooking(token, bookingId, { rating, comment: comment.trim() || null });
        notifySuccessThen(
          navigation,
          isEn ? 'Service marked complete. Thank you for your review.' : 'Layanan ditandai selesai. Terima kasih atas ulasannya.',
          'BookingDetail',
          { bookingId },
        );
      } else {
        await submitReview(token, bookingId, { rating, comment: comment.trim() || null });
        notifySuccessThen(navigation, isEn ? 'Review saved successfully.' : 'Ulasan berhasil disimpan.', () => navigation.goBack());
      }
    } catch (err) {
      setError(err.message || (isEn ? 'Failed to save' : 'Gagal menyimpan'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <ScreenHeader
        title={isComplete ? (isEn ? 'Complete Service' : 'Selesaikan Layanan') : (isEn ? 'Write Review' : 'Beri Ulasan')}
        onBack={() => navigation.goBack()}
      />

      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <Text style={styles.intro}>
          {isComplete
            ? (isEn ? 'Confirm the muthowif service is complete and give a rating.' : 'Konfirmasi bahwa layanan muthowif sudah selesai dan beri penilaian.')
            : (isEn ? 'Share your experience with this muthowif.' : 'Bagikan pengalaman Anda dengan muthowif ini.')}
        </Text>

        {error ? <InlineAlert variant="error">{error}</InlineAlert> : null}

        <Card padding={spacing.lg} elevated={false}>
          <StarRatingPicker label="Rating *" value={rating} onChange={setRating} />
        </Card>

        <Text style={styles.label}>{isEn ? 'Review (optional)' : 'Ulasan (opsional)'}</Text>
        <TextInput
          style={styles.textarea}
          value={comment}
          onChangeText={setComment}
          placeholder={isEn ? 'Tell us about your worship experience...' : 'Ceritakan pengalaman ibadah Anda...'}
          placeholderTextColor={colors.textMuted}
          multiline
          maxLength={2000}
          textAlignVertical="top"
        />

        <Button
          label={isComplete ? (isEn ? 'Complete & Submit Review' : 'Selesaikan & Kirim Ulasan') : (isEn ? 'Save Review' : 'Simpan Ulasan')}
          onPress={handleSubmit}
          loading={loading}
        />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  scroll: { padding: layout.screenPadding, paddingBottom: spacing['3xl'] },
  intro: { ...typography.caption, lineHeight: 22, color: colors.textSecondary, marginBottom: spacing.lg },
  label: { ...typography.label, color: colors.textSecondary, marginBottom: spacing.sm, marginTop: spacing.lg },
  textarea: {
    minHeight: 120,
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
