import Sortable from 'sortablejs';

// SortableJS + Alpine x-for: Sortable moves DOM nodes that Alpine owns. We put the node back where it was
// and hand the move to `onMove(id, toEl, newIndex)`; the data change then makes Alpine re-render correctly.
// Items need data-id; drop containers can carry data-* (read from toEl.dataset).
export function makeSortable(el, { group, onMove, delay = 0, handle, ...rest }) {
  let ref = null;
  return Sortable.create(el, {
    group,
    handle,
    animation: 150,
    delay, // touch: hold before drag (dnd-kit TouchSensor delay)
    delayOnTouchOnly: true,
    draggable: '[data-id]',
    ghostClass: 'opacity-40',
    ...rest,
    onStart(e) {
      ref = e.item.nextSibling;
    },
    onEnd(e) {
      const { item, from, to, newDraggableIndex } = e;
      from.insertBefore(item, ref);
      ref = null;
      if (from === to && e.oldDraggableIndex === newDraggableIndex) return;
      onMove(item.dataset.id, to, newDraggableIndex);
    },
  });
}
