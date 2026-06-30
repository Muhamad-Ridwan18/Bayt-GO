import React, { useState } from 'react';
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
import StarRatingPicker from '../components/StarRatingPicker';
import { completeBooking, submitReview } from '../api/bookings';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

export default function BookingRatingScreen({ navigation, route }) {
  const { token } = useAuth();
  const { bookingId, mode = 'review', initialRating = 5, initialComment = '' } = route.params;

  const isComplete = mode === 'complete';
  const [rating, setRating] = useState(initialRating);
  const [comment, setComment] = useState(initialComment);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async () => {
    if (rating < 1) {
      setError('Pilih rating 1–5 bintang.');
      return;
    }

    setLoading(true);
    setError('');
    try {
      if (isComplete) {
        await completeBooking(token, bookingId, { rating, comment: comment.trim() || null });
        Alert.alert('Berhasil', 'Layanan ditandai selesai. Terima kasih atas ulasannya.', [
          { text: 'OK', onPress: () => navigation.navigate('BookingDetail', { bookingId }) },
        ]);
      } else {
        await submitReview(token, bookingId, { rating, comment: comment.trim() || null });
        Alert.alert('Berhasil', 'Ulasan berhasil disimpan.', [
          { text: 'OK', onPress: () => navigation.goBack() },
        ]);
      }
    } catch (err) {
      setError(err.message || 'Gagal menyimpan');
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <ScreenHeader
        title={isComplete ? 'Selesaikan Layanan' : 'Beri Ulasan'}
        onBack={() => navigation.goBack()}
      />

      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <Text style={styles.intro}>
          {isComplete
            ? 'Konfirmasi bahwa layanan muthowif sudah selesai dan beri penilaian.'
            : 'Bagikan pengalaman Anda dengan muthowif ini.'}
        </Text>

        {error ? <Text style={styles.error}>{error}</Text> : null}

        <StarRatingPicker label="Rating *" value={rating} onChange={setRating} />

        <Text style={styles.label}>Ulasan (opsional)</Text>
        <TextInput
          style={styles.textarea}
          value={comment}
          onChangeText={setComment}
          placeholder="Ceritakan pengalaman ibadah Anda..."
          placeholderTextColor={colors.slate400}
          multiline
          maxLength={2000}
          textAlignVertical="top"
        />

        <TouchableOpacity style={styles.submitBtn} onPress={handleSubmit} disabled={loading} activeOpacity={0.9}>
          <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.submitGradient}>
            {loading ? (
              <ActivityIndicator color={colors.white} />
            ) : (
              <Text style={styles.submitText}>{isComplete ? 'Selesaikan & Kirim Ulasan' : 'Simpan Ulasan'}</Text>
            )}
          </LinearGradient>
        </TouchableOpacity>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  scroll: { padding: 16, paddingBottom: 32 },
  intro: { fontSize: 14, lineHeight: 21, color: colors.slate600, fontWeight: '500', marginBottom: 16 },
  error: { marginBottom: 12, fontSize: 13, color: '#DC2626', fontWeight: '600' },
  label: { fontSize: 12, fontWeight: '800', color: colors.slate600, marginBottom: 8 },
  textarea: {
    minHeight: 120,
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
