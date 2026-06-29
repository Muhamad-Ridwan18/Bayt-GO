import React from 'react';
import { View, Text, TextInput, StyleSheet, TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';

export default function AuthInput({
  label,
  icon,
  secureTextEntry,
  error,
  containerStyle,
  ...props
}) {
  const [hidden, setHidden] = React.useState(Boolean(secureTextEntry));

  return (
    <View style={[styles.wrap, containerStyle]}>
      {label ? <Text style={styles.label}>{label}</Text> : null}
      <View style={[styles.field, error && styles.fieldError]}>
        {icon ? (
          <Ionicons name={icon} size={18} color={colors.slate400} style={styles.icon} />
        ) : null}
        <TextInput
          style={styles.input}
          placeholderTextColor={colors.slate400}
          secureTextEntry={hidden}
          {...props}
        />
        {secureTextEntry ? (
          <TouchableOpacity onPress={() => setHidden((v) => !v)} hitSlop={8}>
            <Ionicons name={hidden ? 'eye-off-outline' : 'eye-outline'} size={18} color={colors.slate400} />
          </TouchableOpacity>
        ) : null}
      </View>
      {error ? <Text style={styles.error}>{error}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { marginBottom: 14 },
  label: { fontSize: 12, fontWeight: '800', color: colors.slate600, marginBottom: 8, marginLeft: 2 },
  field: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.white,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
    paddingHorizontal: 14,
    minHeight: 52,
  },
  fieldError: { borderColor: '#FCA5A5' },
  icon: { marginRight: 10 },
  input: { flex: 1, fontSize: 15, fontWeight: '600', color: colors.slate900, paddingVertical: 12 },
  error: { marginTop: 6, marginLeft: 4, fontSize: 12, color: '#DC2626', fontWeight: '600' },
});
