import React from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';
import PressableScale from '../../ui/PressableScale';
import { colors, layout, radius, spacing, typography } from '../../theme/tokens';
import { resolveMediaUrl } from '../../utils/mediaUrl';

export default function HomeGallery({ items = [], onPress }) {
  if (!items.length) return null;

  return (
    <View style={styles.wrap}>
      <View style={styles.head}>
        <Text style={styles.kicker}>Dokumentasi</Text>
        <Text style={styles.sectionTitle}>Momen Bersama Jamaah</Text>
      </View>
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.row}
        nestedScrollEnabled
      >
        {items.map((item) => {
          const uri = resolveMediaUrl(item.url);
          if (!uri) return null;
          return (
            <PressableScale
              key={item.id}
              onPress={() => onPress?.(item)}
              haptic="light"
              disabled={!item.muthowif_id}
            >
              <View style={styles.card}>
                <Image source={{ uri }} style={styles.image} contentFit="cover" />
                {item.caption ? (
                  <Text style={styles.caption} numberOfLines={1}>{item.caption}</Text>
                ) : null}
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
  head: { paddingHorizontal: layout.screenPadding, marginBottom: spacing.md },
  kicker: {
    ...typography.label,
    color: colors.goldMuted,
    textTransform: 'uppercase',
    letterSpacing: 0.6,
  },
  sectionTitle: {
    marginTop: spacing.xs,
    ...typography.subtitle,
    fontSize: 17,
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    fontWeight: '800',
  },
  row: { paddingHorizontal: layout.screenPadding, gap: spacing.sm, paddingBottom: spacing.xs },
  card: { width: 132 },
  image: {
    width: 132,
    height: 168,
    borderRadius: radius.sm,
    backgroundColor: colors.background,
  },
  caption: {
    marginTop: spacing.xs,
    ...typography.small,
    color: colors.textSecondary,
  },
});
