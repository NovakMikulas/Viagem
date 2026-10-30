import { useState } from 'react';
import ParcelMap from './components/ParcelMap';
import ParcelDetail from './components/ParcelDetail';

function App() {
  const [selectedParcelId, setSelectedParcelId] = useState(null);

  return (
    <div className="app">
      <ParcelMap selectedParcelId={selectedParcelId} onSelectParcel={setSelectedParcelId} />

      {selectedParcelId !== null && (
        <ParcelDetail parcelId={selectedParcelId} onClose={() => setSelectedParcelId(null)} />
      )}
    </div>
  );
}

export default App;
