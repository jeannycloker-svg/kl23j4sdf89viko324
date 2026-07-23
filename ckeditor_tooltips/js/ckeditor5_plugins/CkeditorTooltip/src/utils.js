/*
 * A helper function that retrieves and concatenates all text within the model
 * range.
 * It will grab all items from a range using its getItems() method. Then, it
 * will concatenate all text from the text and textProxy nodes, and skip all
 * the others.
 */
export default function getRangeText( range ) {
  return Array.from(range.getItems()).reduce((rangeText, node) => {
    if (!(node.is('text') || node.is('textProxy'))) {
      return rangeText;
    }

    return rangeText + node.data;
  }, '');
}
