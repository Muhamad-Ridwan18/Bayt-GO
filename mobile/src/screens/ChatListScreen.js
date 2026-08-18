import React, { useCallback } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { FlashList } from '@shopify/flash-list';
import { useFocusEffect } from '@react-navigation/native';
import TabPageHeader from '../components/TabPageHeader';
import ConversationListItem from '../components/ConversationListItem';
import { useAuth } from '../context/AuthContext';
import { useChatInbox } from '../context/ChatInboxContext';
import EmptyState from '../ui/EmptyState';
import ErrorState from '../ui/ErrorState';
import { SkeletonList } from '../ui/Skeleton';
import { colors, layout, spacing, typography } from '../theme/tokens';
import { useLocale } from '../utils/locale';

export default function ChatListScreen({ navigation }) {
  const locale = useLocale();
  const isEn = locale === 'en';
  const { isVerifiedMuthowif } = useAuth();
  const {
    conversations,
    loading,
    refreshing,
    error,
    refresh,
    pullToRefresh,
  } = useChatInbox();

  useFocusEffect(
    useCallback(() => {
      refresh(true);
    }, [refresh]),
  );

  const openConversation = useCallback((item) => {
    navigation.navigate('ChatRoom', {
      bookingId: item.booking_id,
      bookingCode: item.booking_code,
      otherName: item.other_name,
    });
  }, [navigation]);

  const renderItem = useCallback(({ item }) => (
    <ConversationListItem item={item} onPress={() => openConversation(item)} />
  ), [openConversation]);

  const listHeader = conversations.length > 0 ? (
    <Text style={styles.resultCount}>
      {isEn ? `${conversations.length} conversations` : `${conversations.length} percakapan`}
    </Text>
  ) : null;

  if (loading && !refreshing) {
    return (
      <View style={styles.container}>
        <TabPageHeader
          title="Chat"
          subtitle={isEn ? 'Your booking conversations' : 'Percakapan booking Anda'}
        />
        <SkeletonList count={5} style={styles.skeleton} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <TabPageHeader
        title="Chat"
        subtitle={isEn ? 'Your booking conversations' : 'Percakapan booking Anda'}
      />

      {error && conversations.length === 0 ? (
        <ErrorState description={error} onRetry={() => refresh(true)} />
      ) : (
        <FlashList
          data={conversations}
          keyExtractor={(item) => String(item.booking_id)}
          renderItem={renderItem}
          estimatedItemSize={100}
          ListHeaderComponent={listHeader}
          contentContainerStyle={styles.list}
          showsVerticalScrollIndicator={false}
          refreshing={refreshing}
          onRefresh={pullToRefresh}
          ListEmptyComponent={
            error ? (
              <ErrorState description={error} onRetry={() => refresh(true)} />
            ) : (
              <EmptyState
                variant="chat"
                title={isEn ? 'No conversations yet' : 'Belum ada percakapan'}
                description={isEn
                  ? 'Chat becomes available after a booking is confirmed and paid.'
                  : 'Chat tersedia setelah booking dikonfirmasi dan pembayaran berhasil.'}
                actionLabel={isEn ? 'View my bookings' : 'Lihat pesanan saya'}
                onAction={() => navigation.getParent()?.navigate(
                  isVerifiedMuthowif ? 'MuthowifBookingsTab' : 'BookingsTab',
                )}
              />
            )
          }
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  skeleton: {
    paddingHorizontal: layout.screenPadding,
    paddingTop: spacing.lg,
  },
  list: {
    paddingHorizontal: layout.screenPadding,
    paddingBottom: spacing.lg,
  },
  resultCount: {
    ...typography.small,
    color: colors.textSecondary,
    marginBottom: spacing.md,
    marginLeft: spacing.xs,
    fontWeight: '500',
    fontFamily: 'PlusJakartaSans_500Medium',
  },
});
