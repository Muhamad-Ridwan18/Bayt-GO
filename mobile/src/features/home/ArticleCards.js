import React from 'react';
import { Dimensions, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { ChevronRight } from 'lucide-react-native';
import PressableScale from '../../ui/PressableScale';
import { colors, layout, radius, spacing, typography } from '../../theme/tokens';
import { resolveMediaUrl } from '../../utils/mediaUrl';

const { width: SCREEN_W } = Dimensions.get('window');
const CARD_W = SCREEN_W * 0.62;

export default function ArticleCards({ articles = [], onPress, onSeeAll }) {
  if (!articles.length) return null;

  return (
    <View style={styles.wrap}>
      <View style={styles.head}>
        <Text style={styles.sectionTitle}>Artikel & Panduan</Text>
        {onSeeAll ? (
          <PressableScale onPress={onSeeAll} haptic="light">
            <View style={styles.seeAllRow}>
              <Text style={styles.seeAll}>Lihat semua</Text>
              <ChevronRight size={14} color={colors.goldMuted} strokeWidth={2.5} />
            </View>
          </PressableScale>
        ) : null}
      </View>
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.row}
        nestedScrollEnabled
      >
        {articles.map((item) => {
          const thumb = resolveMediaUrl(item.thumbnail);
          return (
            <PressableScale key={item.slug} onPress={() => onPress?.(item)} haptic="light">
              <View style={styles.card}>
                <View style={styles.thumb}>
                  {thumb ? (
                    <Image source={{ uri: thumb }} style={styles.thumbImg} contentFit="cover" />
                  ) : (
                    <View style={styles.thumbFallback} />
                  )}
                </View>
                <View style={styles.body}>
                  <Text style={styles.title} numberOfLines={2}>{item.title}</Text>
                  {item.excerpt ? (
                    <Text style={styles.excerpt} numberOfLines={2}>{item.excerpt}</Text>
                  ) : null}
                </View>
              </View>
            </PressableScale>
          );
        })}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { marginTop: spacing.xl },
  head: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: layout.screenPadding,
    marginBottom: spacing.md,
  },
  sectionTitle: {
    flex: 1,
    ...typography.subtitle,
    fontSize: 17,
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    fontWeight: '800',
  },
  seeAllRow: { flexDirection: 'row', alignItems: 'center', gap: 2 },
  seeAll: { ...typography.caption, fontWeight: '800', color: colors.goldMuted },
  row: { paddingHorizontal: layout.screenPadding, gap: spacing.md, paddingBottom: spacing.xs },
  card: {
    width: CARD_W,
    backgroundColor: colors.card,
    borderRadius: radius.md - 2,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: 'rgba(226, 232, 240, 0.8)',
  },
  thumb: { height: 110, backgroundColor: colors.background },
  thumbImg: { width: '100%', height: '100%' },
  thumbFallback: { flex: 1, backgroundColor: colors.baytgoLight },
  body: { padding: spacing.md },
  title: {
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    fontWeight: '800',
    color: colors.textPrimary,
  },
  excerpt: {
    marginTop: spacing.xs,
    ...typography.small,
    color: colors.textSecondary,
    lineHeight: 16,
  },
});
