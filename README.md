# MageWorx_RewardPointsGraphQl

GraphQL API module for Mageworx [Magento 2 Reward Points](https://www.mageworx.com/magento-2-reward-points.html) extension.

## Installation

**1) Installation using composer (from packagist)**
- Execute the following command: `composer require mageworx/module-rewardpoints-graphql`

**2) Copy-to-paste method**
- Download this module and upload it to the `app/code/MageWorx/RewardPointsGraphQl` directory *(create "RewardPointsGraphQl" first if missing)*

## How to use

### Apply reward points to the shopping cart
  
The **applyMwRewardPointsToCart** mutation is used to apply reward points to the shopping cart.  
  
**Syntax**  
`applyMwRewardPointsToCart(input: ApplyMwRewardPointsToCartInput): ApplyMwRewardPointsToCartOutput`  

The ApplyMwRewardPointsToCartInput object may contain the following attributes:
```
cart_id: String! @doc(description:"The unique ID that identifies the customer's cart")
points: Float @doc(description: "Points value")
```
  
The ApplyMwRewardPointsToCartOutput object contains the Cart object.  
  
**Request:**
```
mutation {
    applyMwRewardPointsToCart(
        input: {
            cart_id: "plhjWW93b8r2zO4kpye2FfWtK2QPmvS3"
            points: 4
        }
    ) {
        cart {
            items {
                product {
                    name
                    sku
                }
                quantity
            }
            mw_applied_reward_points {
                points
                money {
                    value
                    currency
                }
            }
            mw_reward_messages {
                header_message
                minicart_message
                cart_message
                checkout_message
            }
        }
    }
}
```

**Response:**
```
{
    "data": {
        "applyMwRewardPointsToCart": {
            "cart": {
                "items": [
                    {
                        "product": {
                            "name": "Fusion Backpack",
                            "sku": "24-MB02"
                        },
                        "quantity": 1
                    }
                ],
                "mw_applied_reward_points": {
                    "points": 4,
                    "money": {
                        "value": 40,
                        "currency": "USD"
                    }
                },
                "mw_reward_messages": {
                    "header_message": "Header Message: Complete this cart and earn 5.9 points for a discount on your future purchases.",
                    "minicart_message": "Mini-cart Balance Message: Your current reward points balance is $179.60.",
                    "cart_message": "Cart Message: Complete the purchase and earn 5.9 points for a discount on your future purchases.",
                    "checkout_message": "Checkout Message: Complete the purchase and earn 5.9 points for a discount on your future purchases."
                }
            }
        }
    }
}
```
  
  
### Remove reward points from the shopping cart
  
The **removeMwRewardPointsFromCart** mutation is used to remove reward points from the shopping cart. 
  
**Syntax**  
`removeMwRewardPointsFromCart(input: RemoveMwRewardPointsToCartInput): RemoveMwRewardPointsFromCartOutput`  

The RemoveMwRewardPointsToCartInput object may contain the following attributes:
```
cart_id: String! @doc(description:"The unique ID that identifies the customer's cart")
```
  
The RemoveMwRewardPointsFromCartOutput object contains the Cart object.  
  
**Request:**
```
mutation {
    removeMwRewardPointsFromCart(
        input: {
            cart_id: "plhjWW93b8r2zO4kpye2FfWtK2QPmvS3"
        }
    ) {
        cart {
            items {
                product {
                    name
                    sku
                }
                quantity
            }
            mw_applied_reward_points {
                points
                money {
                    value
                    currency
                }
            }
            mw_reward_messages {
                header_message
                minicart_message
                cart_message
                checkout_message
            }
        }
    }
}
```

**Response:**
```
{
    "data": {
        "removeMwRewardPointsFromCart": {
            "cart": {
                "items": [
                    {
                        "product": {
                            "name": "Fusion Backpack",
                            "sku": "24-MB02"
                        },
                        "quantity": 1
                    }
                ],
                "mw_applied_reward_points": null,
                "mw_reward_messages": {
                    "header_message": "Header Message: Complete this cart and earn 5.9 points for a discount on your future purchases.",
                    "minicart_message": "Mini-cart Balance Message: Your current reward points balance is $179.60.",
                    "cart_message": "Cart Message: Complete the purchase and earn 5.9 points for a discount on your future purchases.",
                    "checkout_message": "Checkout Message: Complete the purchase and earn 5.9 points for a discount on your future purchases."
                }
            }
        }
    }
}
```
  
  
### Additional data in Cart object
  
The following attributes are added for Cart:  
```
mw_applied_reward_points: MwRewardPointsBalance @doc(description: "Applied reward points details")
mw_reward_messages: CartRewardMessages @doc(description: "Cart Reward Messages")
```

MwRewardPointsBalance attributes:
```
points: Float! @doc(description: "Points value")
money: Money! @doc(description: "The amount of reward points in the currency of the store")
```

CartRewardMessages attributes:
```
header_message: String! @doc(description: "Header Reward Message")
minicart_message: String! @doc(description: "Mini-cart Reward Message")
cart_message: String! @doc(description: "Cart Reward Message")
checkout_message: String! @doc(description: "Checkout Reward Message")
```
  
**Request:**
```
{
  customerCart {
    items {
      product {
        name
        sku
      }
      quantity
    }
    mw_applied_reward_points {
        points
        money {
            value
            currency
        }
    }
    mw_reward_messages {
        header_message
        minicart_message
        cart_message
        checkout_message
    }
  }
}
```

**Response:**
```
{
    "data": {
        "customerCart": {
            "items": [
                {
                    "product": {
                        "name": "Fusion Backpack",
                        "sku": "24-MB02"
                    },
                    "quantity": 1
                }
            ],
            "mw_applied_reward_points": {
                "points": 3,
                "money": {
                    "value": 30,
                    "currency": "USD"
                }
            },
            "mw_reward_messages": {
                "header_message": "Header Message: Complete this cart and earn 5.9 points for a discount on your future purchases.",
                "minicart_message": "Mini-cart Balance Message: Your current reward points balance is $179.60.",
                "cart_message": "Cart Message: Complete the purchase and earn 5.9 points for a discount on your future purchases.",
                "checkout_message": "Checkout Message: Complete the purchase and earn 5.9 points for a discount on your future purchases."
            }
        }
    }
}
```
  
  
### Additional data in ProductInterface  
  
The following attribute is added for ProductInterface:  
```
mw_reward_messages: ProductRewardMessages @doc(description: "Product Reward Messages")
```  
ProductRewardMessages attributes:
```
product_message: String! @doc(description: "Product Reward Message")
category_message: String! @doc(description: "Category Reward Message")
```
  
**Request:**
```
{
  products(filter: {sku: {eq: "24-WB04"}}) {
    items {
      name
      sku
      price {
          minimalPrice {
              amount {
                  value
                  currency
              }
          }
      }
      mw_reward_messages {
          product_message
          category_message
      }
    }
  }
}
```

**Response:**
```
{
    "data": {
        "products": {
            "items": [
                {
                    "name": "Push It Messenger Bag",
                    "sku": "24-WB04",
                    "price": {
                        "minimalPrice": {
                            "amount": {
                                "value": 45,
                                "currency": "USD"
                            }
                        }
                    },
                    "mw_reward_messages": {
                        "product_message": "Product Reward Message: Earn 4.5 points.",
                        "category_message": "Category Reward Message: Earn 4.5 points."
                    }
                }
            ]
        }
    }
}
```
  
  
### Additional data in Customer object
  
The attribute mw_reward_points is added for Customer:  
```
mw_reward_points: MwRewardPoints @doc(description: "Customer reward points details")
```

MwRewardPoints attributes:
```
balance: MwRewardPointsBalance @doc(description: "Current balance")
expiration_date: String! @doc(description: "Expiration Date")
transactions: [MwRewardPointsTransaction] @doc(description: "An array of applied Gift Cards Reward Points Transactions")
```
MwRewardPointsBalance attributes:
```
points: Float! @doc(description: "Points value")
money: Money! @doc(description: "The amount of reward points in the currency of the store")
```
MwRewardPointsTransaction attributes:
```
balance: Float! @doc(description: "Points Balance")
delta: String! @doc(description: "Points Delta")
message: String! @doc(description: "Message")
date: String! @doc(description: "Date")
```
  
**Request:**
```
{
  customer {
    firstname
    lastname
    email
    mw_reward_points {
        balance {
            points
            money {
                value
                currency
            }
        }
        expiration_date
        transactions {
            balance
            delta
            message
            date
        }
    }
  }
}
```

**Response:**
```
{
    "data": {
        "customer": {
            "firstname": "Veronica",
            "lastname": "Costello",
            "email": "roni_cost@example.com",
            "mw_reward_points": {
                "balance": {
                    "points": 17.96,
                    "money": {
                        "value": 179.6,
                        "currency": "USD"
                    }
                },
                "expiration_date": "5/19/22",
                "transactions": [
                    {
                        "balance": 17.96,
                        "delta": "+2.7000",
                        "message": "The reward points were added for the completed order 000000020",
                        "date": "4/18/22, 4:31 PM"
                    },
                    {
                        "balance": 15.26,
                        "delta": "+5.2600",
                        "message": "The reward points were added for the completed order 000000018",
                        "date": "4/14/22, 4:02 AM"
                    },
                    {
                        "balance": 10,
                        "delta": "+10.0000",
                        "message": "Updated by admin.",
                        "date": "12/7/21, 1:51 AM"
                    }
                ]
            }
        }
    }
}
```
