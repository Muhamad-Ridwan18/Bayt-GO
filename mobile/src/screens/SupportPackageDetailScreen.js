import React, { useCallback, useState } from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { ChevronRight, Star, Users } from 'lucide-react-native';
import ScreenHeader from '../components/ScreenHeader';
import { parseIsoDateTime } from '../components/DatePickerField';
import { fetchSupportPackageDetail } from '../api/supportCatalog';
import { useAuth } from '../context/AuthContext';
import AppImage from '../ui/AppImage';
import Button from '../ui/Button';
import Card from '../ui/Card';
import ErrorState from '../ui/ErrorState';
import PressableScale from '../ui/PressableScale';
import { SkeletonList } from '../ui/Skeleton';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { formatIdr } from '../utils/format';
import { navigateRoot } from '../navigation/rootNavigation';

export default function SupportPackageDetailScreen({ navigation, route }) {
  const { token, isAuthenticated } = useAuth();
  const { id, startsAt: startsAtParam, startsAtDate, packagePreview } = route.params || {};
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [pkg, setPkg] = useState(packagePreview || null);
  const startsAt = startsAtParam || startsAtDate || '';

  const load = useCallback(async () => {
    if (!id) return;
    setLoading(true);
    try {
      const data = await fetchSupportPackageDetail({
        token,
        id,
        startsAt: startsAt || undefined,
      });
      setPkg(data.data || data);
      setError(null);
    } catch (err) {
      setError(err.message || 'Gagal memuat detail paket');
    } finally {
      setLoading(false);
    }
  }, [token, id, startsAt]);

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load]),
  );

  const handleBook = () => {
    if (!isAuthenticated) {
      navigateRoot(navigation, 'Login');
      return;
    }
    navigation.navigate('SupportPackageBook', {
      id,
      startsAt,
      packagePreview: pkg,
    });
  };

  const openMuthowifProfile = () => {
    const profileId = pkg?.muthowif?.profile_id;
    if (!profileId) return;
    navigation.navigate('MuthowifDetail', { id: profileId });
  };

  const slotLabel = startsAt
    ? parseIsoDateTime(startsAt).toLocaleString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
    : null;

  return (
    <View style={styles.flex}>
      <ScreenHeader title="Detail paket" onBack={() => navigation.goBack()} />
      {loading && !pkg ? <SkeletonList count={3} /> : null}
      {!loading && error && !pkg ? <ErrorState description={error} onRetry={load} /> : null}

      {pkg ? (
        <ScrollView
          contentContainerStyle={styles.content}
          refreshControl={<RefreshControl refreshing={loading} onRefresh={load} />}
        >
          <Card style={styles.card}>
            <Text style={styles.name}>{pkg.name}</Text>
            <Text style={styles.category}>{pkg.category_label}</Text>
            <Text style={styles.price}>{formatIdr(pkg.price)}</Text>
            {pkg.description ? <Text style={styles.desc}>{pkg.description}</Text> : null}
            <View style={styles.metaRow}>
              <Users size={14} color={colors.textMuted} />
              <Text style={styles.meta}>
                {pkg.min_pilgrims}–{pkg.max_pilgrims} jamaah
              </Text>
            </View>
          </Card>

          <Card style={styles.card}>
            <View style={styles.muthowifRow}>
              <AppImage uri={pkg.muthowif?.avatar} name={pkg.muthowif?.name} style={styles.avatar} />
              <View style={styles.flex}>
                <Text style={styles.muthowifName}>{pkg.muthowif?.name}</Text>
                <View style={styles.metaRow}>
                  <Star size={12} color={colors.goldMuted} fill={colors.goldMuted} />
                  <Text style={styles.meta}>
                    {pkg.muthowif?.average_rating || 0} · {pkg.muthowif?.reviews_count || 0} ulasan
                  </Text>
                </View>
                {pkg.muthowif?.work_location ? (
                  <Text style={styles.meta}>{pkg.muthowif.work_location}</Text>
                ) : null}
              </View>
            </View>
            {pkg.muthowif?.profile_id ? (
              <PressableScale onPress={openMuthowifProfile} haptic="light" style={styles.profileLink}>
                <Text style={styles.profileLinkText}>Lihat profil muthowif</Text>
                <ChevronRight size={16} color={colors.baytgo} strokeWidth={2} />
              </PressableScale>
            ) : null}
          </Card>

          {slotLabel ? (
            <View style={styles.slotBanner}>
              <Text style={styles.slotText}>Ketersediaan dipilih: {slotLabel}</Text>
            </View>
          ) : null}

          <Button label="Pesan layanan" onPress={handleBook} />
        </ScrollView>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: colors.background },
  content: {
    paddingHorizontal: layout.screenPadding,
    paddingBottom: spacing.xxl,
    gap: spacing.md,
  },
  card: { gap: spacing.sm },
  name: {
    ...typography.title,
    fontSize: 22,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.textPrimary,
  },
  category: { ...typography.caption, color: colors.textMuted },
  price: {
    ...typography.subtitle,
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
  },
  desc: { ...typography.body, color: colors.textSecondary, lineHeight: 22 },
  metaRow: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  meta: { ...typography.small, color: colors.textMuted },
  muthowifRow: { flexDirection: 'row', gap: spacing.md, alignItems: 'center' },
  avatar: { width: 56, height: 56, borderRadius: 28, backgroundColor: colors.border },
  muthowifName: {
    ...typography.caption,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  profileLink: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: spacing.md,
    paddingTop: spacing.md,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  profileLinkText: {
    ...typography.caption,
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_700Bold',
  },
  slotBanner: {
    backgroundColor: colors.baytgoLight,
    borderRadius: radius.sm,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  slotText: {
    ...typography.small,
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_600SemiBold',
    lineHeight: 18,
  },
});
