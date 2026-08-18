import React, { useCallback, useState } from 'react';
import { Linking, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { useFocusEffect } from '@react-navigation/native';
import ScreenHeader from '../components/ScreenHeader';
import { fetchCampaign } from '../api/home';
import { Button, ErrorState, SkeletonList } from '../ui';
import { colors, layout, spacing, typography } from '../theme/tokens';
import { resolveMediaUrl } from '../utils/mediaUrl';
import { WEB_BASE_URL } from '../config/api';
import { useCountdown } from '../hooks/useCountdown';

function openCta(url) {
  if (!url) return;
  const trimmed = String(url).trim();
  const href = trimmed.startsWith('http') ? trimmed : `${WEB_BASE_URL.replace(/\/$/, '')}/${trimmed.replace(/^\//, '')}`;
  Linking.openURL(href);
}

export default function CampaignDetailScreen({ navigation, route }) {
  const { slug, preview } = route.params || {};
  const [campaign, setCampaign] = useState(preview || null);
  const [loading, setLoading] = useState(!preview);
  const [error, setError] = useState(null);
  const due = useCountdown(campaign?.end_date);

  const load = useCallback(async () => {
    if (!slug) return;
    try {
      const data = await fetchCampaign(slug);
      setCampaign(data);
      setError(null);
    } catch (err) {
      setError(err.message || 'Kampanye tidak ditemukan');
    } finally {
      setLoading(false);
    }
  }, [slug]);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  const banner = resolveMediaUrl(campaign?.banner_url);

  return (
    <View style={styles.container}>
      <ScreenHeader title={campaign?.title || 'Kampanye'} onBack={() => navigation.goBack()} />
      {loading ? <SkeletonList count={2} style={styles.pad} /> : null}
      {error && !campaign ? <ErrorState description={error} onRetry={load} /> : null}
      {campaign ? (
        <ScrollView contentContainerStyle={styles.scroll}>
          {banner ? (
            <Image source={{ uri: banner }} style={styles.banner} contentFit="cover" />
          ) : (
            <View style={[styles.banner, { backgroundColor: campaign.theme_color || colors.baytgo }]} />
          )}
          <Text style={styles.title}>{campaign.title}</Text>
          {due.label && !due.expired ? (
            <Text style={styles.remain}>Berakhir dalam {due.label}</Text>
          ) : null}
          {campaign.body ? <Text style={styles.body}>{campaign.body}</Text> : null}
          {campaign.cta_text && campaign.cta_url ? (
            <Button label={campaign.cta_text} onPress={() => openCta(campaign.cta_url)} />
          ) : null}
        </ScrollView>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  pad: { padding: layout.screenPadding },
  scroll: { padding: layout.screenPadding, paddingBottom: spacing['4xl'] },
  banner: {
    width: '100%',
    height: 180,
    borderRadius: 20,
    backgroundColor: colors.background,
    marginBottom: spacing.lg,
  },
  title: {
    ...typography.title,
    fontSize: 22,
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
  },
  remain: {
    marginTop: spacing.sm,
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: '#B45309',
    fontVariant: ['tabular-nums'],
  },
  body: {
    marginTop: spacing.lg,
    marginBottom: spacing.xl,
    ...typography.caption,
    color: colors.textSecondary,
    lineHeight: 22,
  },
});
