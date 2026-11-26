
import pandas as pd
import os

input_file = "customer_meter_mysql.csv"
output_folder = "split_data"

os.makedirs(output_folder, exist_ok=True)

chunk_size = 20000

for i, chunk in enumerate(
    pd.read_csv(input_file, chunksize=chunk_size)
):
    filename = f"{output_folder}/customer_part_{i+1}.csv"
    chunk.to_csv(filename, index=False)

    print("Created:", filename)

print("CSV splitting completed!")
