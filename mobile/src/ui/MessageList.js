import React, { forwardRef, useCallback, useImperativeHandle, useRef } from 'react';
import { StyleSheet, View } from 'react-native';
import { FlashList } from '@shopify/flash-list';

const MessageList = forwardRef(function MessageList(
  {
    data,
    renderItem,
    keyExtractor,
    ListEmptyComponent,
    ListHeaderComponent,
    estimatedItemSize = 84,
    contentContainerStyle,
    onContentSizeChange,
  },
  ref,
) {
  const listRef = useRef(null);

  const scrollToEnd = useCallback((animated = false) => {
    listRef.current?.scrollToEnd({ animated });
  }, []);

  useImperativeHandle(ref, () => ({
    scrollToEnd,
  }));

  const handleContentSizeChange = useCallback(() => {
    scrollToEnd(false);
    onContentSizeChange?.();
  }, [onContentSizeChange, scrollToEnd]);

  return (
    <View style={styles.wrap}>
      <FlashList
        ref={listRef}
        data={data}
        renderItem={renderItem}
        keyExtractor={keyExtractor}
        estimatedItemSize={estimatedItemSize}
        drawDistance={320}
        contentContainerStyle={contentContainerStyle}
        ListEmptyComponent={ListEmptyComponent}
        ListHeaderComponent={ListHeaderComponent}
        onContentSizeChange={handleContentSizeChange}
        keyboardShouldPersistTaps="handled"
        getItemType={(item) => (item?.image_url || item?.attachments?.length ? 'rich' : 'text')}
      />
    </View>
  );
});

export default MessageList;

const styles = StyleSheet.create({
  wrap: { flex: 1 },
});
