import { useEffect, useState } from 'react';
import { getParcel } from '../api/services/parcelService';
import { getLandTypeColor } from '../constants/landTypes';

function ParcelDetail({ parcelId, onClose }) {
  const [parcel, setParcel] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    let isCurrent = true;

    setParcel(null);
    setError(null);

    getParcel(parcelId)
      .then((data) => {
        if (isCurrent) {
          setParcel(data);
        }
      })
      .catch((err) => {
        if (isCurrent) {
          setError(getErrorMessage(err));
        }
      });

    return () => {
      isCurrent = false;
    };
  }, [parcelId]);

  return (
    <aside className="parcel-detail">
      <button className="parcel-detail-close" onClick={onClose} title="Zavřít">
        ×
      </button>

      {error && <p>Detail se nepodařilo načíst: {error}</p>}

      {!error && !parcel && <p>Načítám…</p>}

      {parcel && (
        <>
          <h2>Parcela {parcel.label}</h2>
          <p className="parcel-detail-subtitle">
            k.ú. {parcel.cadastral_unit.name} ({parcel.cadastral_unit.code})
          </p>

          <dl>
            <dt>Výměra</dt>
            <dd>{formatArea(parcel.area)}</dd>

            <dt>Druh pozemku</dt>
            <dd>
              <span
                className="land-type-color"
                style={{ background: getLandTypeColor(parcel.land_type.code) }}
              />
              {parcel.land_type.name || '–'}
            </dd>

            <dt>Poslední změna</dt>
            <dd>{formatDate(parcel.valid_from)}</dd>

            <dt>ID v katastru</dt>
            <dd>{parcel.id}</dd>
          </dl>
        </>
      )}
    </aside>
  );
}

function formatArea(area) {
  if (area === null) {
    return '–';
  }
  return area.toLocaleString('cs-CZ') + ' m²';
}

function formatDate(isoDate) {
  if (isoDate === null) {
    return '–';
  }
  return new Date(isoDate).toLocaleDateString('cs-CZ');
}

function getErrorMessage(err) {
  if (err.response && err.response.data && err.response.data.error) {
    return err.response.data.error;
  }
  return err.message;
}

export default ParcelDetail;
