import { useEffect } from 'react';
import { View, Text } from 'react-native';
import { supabase } from '../lib/supabase';

export default function App() {

  useEffect(() => {
    testConnection();
  }, []);

  async function testConnection() {
    const { data, error } = await supabase
      .from('users')
      .select('*');

    console.log('DATA:', data);
    console.log('ERROR:', error);
  }

  return (
    <View style={{ marginTop: 50 }}>
      <Text>Supabase Connected 🚀</Text>
    </View>
  );
}